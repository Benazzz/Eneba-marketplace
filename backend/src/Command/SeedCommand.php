<?php

namespace App\Command;

use App\Entity\Banner;
use App\Entity\GameKey;
use App\Entity\HomeBlock;
use App\Entity\Offer;
use App\Entity\Order;
use App\Entity\Product;
use App\Entity\Seller;
use App\Entity\User;
use App\Seed\SeedCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fills an empty database with demo data: catalog, sellers, offers, keys, homepage
 * layout, users and past orders (so the best-sellers block has real sales to rank).
 *
 *   php bin/console app:seed             seed, or fail if data already exists
 *   php bin/console app:seed --if-empty  seed, or skip quietly if data already exists (start.sh)
 *
 * It never deletes or duplicates data. The whole seed is one transaction, so it either
 * completes or leaves the database empty. A fixed random seed gives every environment
 * the same products, offers and prices.
 */
#[AsCommand(name: 'app:seed', description: 'Fill an empty database with demo data')]
final class SeedCommand extends Command
{
    /** Public demo account, listed in the README so reviewers can sign in. Not a secret. */
    public const DEMO_EMAIL = 'demo@example.com';
    public const DEMO_PASSWORD = 'demo12345';

    private const RANDOM_SEED = 2027;
    private const BUYER_COUNT = 5;
    private const ORDER_COUNT = 40;
    private const DEMO_ORDER_COUNT = 3;
    private const POPULAR_WEIGHT = 6; // a popular product is 6x more likely to appear in past orders
    private const KEY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O or 1/I look-alikes

    private const TABLES = ['product', 'seller', 'offer', 'game_key', 'users', 'api_token', 'orders', 'order_item', 'banner', 'home_block'];

    private Randomizer $random;

    /** @var array<string, true> key codes generated so far, to guarantee uniqueness */
    private array $usedCodes = [];

    // Constructor injection, like ASP.NET Core DI: Symfony autowires both services by type.
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('if-empty', null, InputOption::VALUE_NONE, 'Skip (exit code 0) if the database already has products');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $productCount = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM product');
        if ($productCount > 0) {
            if ($input->getOption('if-empty')) {
                $io->note(sprintf('Database already seeded (%d products), skipping.', $productCount));

                return Command::SUCCESS;
            }
            $io->error('Database already has data. Refusing to seed again (use --if-empty to skip instead).');

            return Command::FAILURE;
        }

        $this->random = new Randomizer(new Mt19937(self::RANDOM_SEED));

        // Runs the callback in a transaction, flushes, then commits (or rolls back on an exception).
        $this->em->wrapInTransaction(function (): void {
            $sellers = $this->createSellers();
            $products = $this->createProducts();
            $stock = $this->createOffersAndKeys($products, $sellers);
            $this->createHomepage();
            $users = $this->createUsers();
            $this->createPastOrders($users, $products, $stock);
        });

        $io->success('Demo data seeded. Demo login: '.self::DEMO_EMAIL.' / '.self::DEMO_PASSWORD);
        $io->table(['Table', 'Rows'], $this->rowCounts());

        return Command::SUCCESS;
    }

    /** @return list<Seller> */
    private function createSellers(): array
    {
        $sellers = [];
        foreach (SeedCatalog::SELLERS as $row) {
            $sellers[] = $seller = new Seller($row['name'], $row['rating'], $row['verified']);
            $this->em->persist($seller);
        }

        return $sellers;
    }

    /** @return list<array{product: Product, popular: bool}> */
    private function createProducts(): array
    {
        $products = [];
        foreach (SeedCatalog::PRODUCTS as $row) {
            $product = new Product(
                self::slug($row['name']),
                $row['name'],
                $row['description'],
                str_replace('{id}', (string) $row['appId'], SeedCatalog::COVER_URL),
                $row['platform'],
                $row['region'],
                $row['msrpCents'],
                new \DateTimeImmutable($row['released']),
            );
            $this->em->persist($product);
            $products[] = ['product' => $product, 'popular' => $row['popular'] ?? false];
        }

        return $products;
    }

    /**
     * 2–5 offers per product from different sellers, each with its own stock of keys.
     *
     * @param list<array{product: Product, popular: bool}> $products
     * @param list<Seller>                                 $sellers
     *
     * @return array<int, list<GameKey>> available keys per offer (keyed by spl_object_id of the offer)
     */
    private function createOffersAndKeys(array $products, array $sellers): array
    {
        $stock = [];
        foreach ($products as ['product' => $product]) {
            $offerCount = $this->random->getInt(2, 5);
            foreach ($this->random->pickArrayKeys($sellers, $offerCount) as $sellerIndex) {
                $offer = new Offer($product, $sellers[$sellerIndex], $this->randomPrice($product->getMsrpCents()));
                $this->em->persist($offer);

                // About 1 offer in 10 is (almost) sold out, so out-of-stock states can be shown.
                $keyCount = $this->random->getInt(1, 10) === 1 ? $this->random->getInt(0, 1) : $this->random->getInt(4, 12);
                $keys = [];
                for ($i = 0; $i < $keyCount; ++$i) {
                    $keys[] = $key = new GameKey($offer, $this->newKeyCode());
                    $this->em->persist($key);
                }
                $stock[spl_object_id($offer)] = $keys;
            }
        }

        return $stock;
    }

    private function createHomepage(): void
    {
        foreach (SeedCatalog::BANNERS as $i => $row) {
            $this->em->persist(new Banner(
                $row['title'],
                $row['subtitle'],
                str_replace('{id}', (string) $row['appId'], SeedCatalog::HERO_URL),
                '/product/'.self::slug($row['product']),
                $i + 1,
            ));
        }
        foreach (SeedCatalog::HOME_BLOCKS as $i => $row) {
            $this->em->persist(new HomeBlock($row['type'], $row['title'], $i + 1));
        }
    }

    /** @return array{demo: User, buyers: list<User>} */
    private function createUsers(): array
    {
        $demo = $this->createUser(self::DEMO_EMAIL, self::DEMO_PASSWORD);

        // Other customers who "bought" things in the past. Their passwords are random and
        // thrown away, so nobody can sign in as them.
        $buyers = [];
        for ($i = 1; $i <= self::BUYER_COUNT; ++$i) {
            $buyers[] = $this->createUser("buyer{$i}@example.com", bin2hex(random_bytes(16)));
        }

        return ['demo' => $demo, 'buyers' => $buyers];
    }

    private function createUser(string $email, string $plainPassword): User
    {
        $user = new User($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $this->em->persist($user);

        return $user;
    }

    /**
     * Past orders over the last 60 days. Each line really takes keys from an offer's
     * stock (marked sold, linked to the order item), so stock and history stay consistent.
     *
     * @param array{demo: User, buyers: list<User>}        $users
     * @param list<array{product: Product, popular: bool}> $products
     * @param array<int, list<GameKey>>                    $stock
     */
    private function createPastOrders(array $users, array $products, array &$stock): void
    {
        // Weighted pool: popular products appear several times, so they get picked more often.
        $pool = [];
        foreach ($products as ['product' => $product, 'popular' => $popular]) {
            array_push($pool, ...array_fill(0, $popular ? self::POPULAR_WEIGHT : 1, $product));
        }

        for ($i = 0; $i < self::ORDER_COUNT; ++$i) {
            $user = $i < self::DEMO_ORDER_COUNT
                ? $users['demo']
                : $users['buyers'][$this->random->getInt(0, self::BUYER_COUNT - 1)];
            $createdAt = new \DateTimeImmutable(sprintf('-%d minutes', $this->random->getInt(60, 60 * 24 * 60)));
            $order = new Order($user, '4242', $createdAt);

            $lineCount = $this->random->getInt(1, 10) <= 7 ? 1 : 2;
            $usedProducts = [];
            for ($line = 0; $line < $lineCount; ++$line) {
                $product = $pool[$this->random->getInt(0, count($pool) - 1)];
                if (isset($usedProducts[spl_object_id($product)])) {
                    continue; // same product twice in one order: just make it a shorter order
                }
                $usedProducts[spl_object_id($product)] = true;

                $quantity = $this->random->getInt(1, 10) === 1 ? 2 : 1;
                $inStock = array_values(array_filter(
                    $product->getOffers()->toArray(),
                    fn (Offer $offer) => count($stock[spl_object_id($offer)]) >= $quantity,
                ));
                if ($inStock === []) {
                    continue;
                }

                $offer = $inStock[$this->random->getInt(0, count($inStock) - 1)];
                $item = $order->addItem($offer, $quantity);
                for ($k = 0; $k < $quantity; ++$k) {
                    $item->deliver(array_pop($stock[spl_object_id($offer)]), $createdAt);
                }
            }

            if (!$order->getItems()->isEmpty()) {
                $this->em->persist($order); // cascades to its items
            }
        }
    }

    /** 45–105% of MSRP, ending in 9 cents (e.g. 35.99). */
    private function randomPrice(int $msrpCents): int
    {
        $cents = intdiv($msrpCents * $this->random->getInt(45, 105), 100);

        return max(99, intdiv($cents, 10) * 10 + 9);
    }

    /** e.g. "7KQ2M-XH4PD-9TRWA", unique within the seed. */
    private function newKeyCode(): string
    {
        do {
            $code = implode('-', str_split($this->random->getBytesFromString(self::KEY_ALPHABET, 15), 5));
        } while (isset($this->usedCodes[$code]));
        $this->usedCodes[$code] = true;

        return $code;
    }

    /** "Baldur's Gate 3 (PS5) PSN Key EUROPE" -> "baldurs-gate-3-ps5-psn-key-europe" */
    private static function slug(string $name): string
    {
        $ascii = strtr($name, ['ö' => 'o', 'ë' => 'e', 'é' => 'e', 'ó' => 'o', "'" => '']);

        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');
    }

    /** @return list<array{string, int}> */
    private function rowCounts(): array
    {
        $connection = $this->em->getConnection();

        return array_map(
            fn (string $table) => [$table, (int) $connection->fetchOne("SELECT COUNT(*) FROM $table")],
            self::TABLES,
        );
    }
}
