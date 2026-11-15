<?php

namespace App\Tests\Checkout;

use App\Checkout\CheckoutService;
use App\Checkout\OutOfStockException;
use App\Checkout\PaymentSimulator;
use App\Dto\CardInput;
use App\Entity\GameKey;
use App\Entity\KeyStatus;
use App\Entity\Offer;
use App\Entity\Product;
use App\Entity\Seller;
use App\Entity\User;
use App\Security\ApiTokenService;
use App\Tests\Support\InMemoryLogger;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Checkout correctness (PLAN.md §10 step 11): a key is never sold twice, and a failed checkout
 * (out of stock, declined card) saves nothing.
 *
 * Runs against the real test database (app_test), because the guarantees come from PostgreSQL
 * (row locks, the transaction). Isolation without an extra package:
 * - setUp opens a transaction, and tearDown rolls it back, so nothing a test does is kept.
 * - CheckoutService's own transaction then becomes a SAVEPOINT inside it (DBAL nests transactions that way).
 * - disableReboot() keeps one kernel, and so one database connection, for all requests of a test. Otherwise
 *   the HTTP client would boot a fresh kernel per request, with a new connection outside our transaction.
 * - Each test creates its own products, offers and keys, so it doesn't depend on the seed or use up its stock.
 *
 * WebTestCase is like WebApplicationFactory in ASP.NET Core: the requests go through the whole app
 * (routing, firewall, validation, controller) in memory, without a real HTTP server.
 */
final class CheckoutServiceTest extends WebTestCase
{
    private const DECLINED_CARD = ['number' => PaymentSimulator::DECLINED_CARD, 'expiry' => '12/99', 'cvc' => '123', 'name' => 'Test Buyer'];

    private KernelBrowser $client;
    private Connection $connection;
    private EntityManagerInterface $em;

    private User $user;
    private string $token;
    /** 3 keys in stock */
    private Offer $offerA;
    /** 2 keys in stock */
    private Offer $offerB;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();
        $this->connection = $container->get(Connection::class);
        $this->em = $container->get(EntityManagerInterface::class);

        $this->connection->beginTransaction();
        $this->createFixtures();
        $this->logger()->takeRecords();
    }

    protected function tearDown(): void
    {
        if ($this->connection->isTransactionActive()) {
            $this->connection->rollBack();
        }
        parent::tearDown();
    }

    /**
     * Buying all of an offer's keys in two orders, then trying once more: every key goes to exactly one
     * order, no code is delivered twice, and the extra attempt is refused as out of stock.
     */
    public function testAKeyIsNeverSoldTwice(): void
    {
        $checkout = static::getContainer()->get(CheckoutService::class);
        $card = new CardInput(PaymentSimulator::APPROVED_CARD, '12/99', '123', 'Test Buyer');
        $offerId = (int) $this->offerA->getId();

        $firstOrder = $checkout->checkout($this->user, [$offerId => 2], $card);
        $secondOrder = $checkout->checkout($this->user, [$offerId => 1], $card);

        try {
            $checkout->checkout($this->user, [$offerId => 1], $card);
            self::fail('A third checkout should find no key left.');
        } catch (OutOfStockException $e) {
            self::assertSame($offerId, $e->offerId);
            self::assertSame(0, $e->available);
        }

        $firstCodes = $this->deliveredCodes($firstOrder);
        $secondCodes = $this->deliveredCodes($secondOrder);
        self::assertCount(2, $firstCodes);
        self::assertCount(1, $secondCodes);

        $allCodes = [...$firstCodes, ...$secondCodes];
        self::assertSame($allCodes, array_unique($allCodes), 'A key code was delivered twice.');

        $offerCodes = $this->connection->fetchFirstColumn('SELECT code FROM game_key WHERE offer_id = ? ORDER BY code', [$offerId]);
        sort($allCodes);
        self::assertSame($offerCodes, $allCodes, 'Every key of the offer should be in exactly one order.');

        self::assertSame(0, $this->countKeys($offerId, KeyStatus::Available));
        self::assertSame('4242', $this->connection->fetchOne('SELECT card_last4 FROM orders WHERE id = ?', [$firstOrder]));
    }

    /**
     * The first line is in stock, the second asks for more than there is. The whole checkout fails with 409,
     * so the first line's keys must not be sold either.
     */
    public function testOutOfStockReturns409AndSavesNothing(): void
    {
        $before = $this->snapshot();

        $response = $this->postCheckout(
            [['offerId' => $this->offerA->getId(), 'quantity' => 1], ['offerId' => $this->offerB->getId(), 'quantity' => 3]],
            ['number' => PaymentSimulator::APPROVED_CARD, 'expiry' => '12/99', 'cvc' => '123', 'name' => 'Test Buyer'],
        );

        self::assertSame(409, $this->client->getResponse()->getStatusCode());
        self::assertSame('out_of_stock', $response['error']['code']);
        self::assertSame($this->offerB->getId(), $response['error']['offerId']);
        self::assertStringContainsString('Checkout Test Game B', $response['error']['message'], 'The message should name the item.');
        self::assertSame($before, $this->snapshot(), 'Nothing may be saved when a line is out of stock.');
    }

    public function testDeclinedCardReturns402AndSavesNothing(): void
    {
        $before = $this->snapshot();

        $response = $this->postCheckout([['offerId' => $this->offerA->getId(), 'quantity' => 1]], self::DECLINED_CARD);

        self::assertSame(402, $this->client->getResponse()->getStatusCode());
        self::assertSame('card_declined', $response['error']['code']);
        self::assertSame($before, $this->snapshot(), 'Nothing may be saved when the card is declined.');
        self::assertStringNotContainsString(PaymentSimulator::DECLINED_CARD, (string) $this->client->getResponse()->getContent());
    }

    /**
     * Only the last 4 digits may ever be kept, and the full number must never be logged. Checked for a
     * request that fails validation (422: an exception carrying the input) and one that is declined (402).
     */
    public function testCardNumberNeverReachesTheLogs(): void
    {
        $this->postCheckout(
            [['offerId' => $this->offerA->getId(), 'quantity' => 1]],
            [...self::DECLINED_CARD, 'expiry' => '01/20'], // expired → 422
        );
        self::assertSame(422, $this->client->getResponse()->getStatusCode());

        $this->postCheckout([['offerId' => $this->offerA->getId(), 'quantity' => 1]], self::DECLINED_CARD); // 402
        self::assertSame(402, $this->client->getResponse()->getStatusCode());

        // Every level is recorded (down to debug: matched routes, SQL statements with their parameters),
        // so both requests must have left records, or this test would check nothing.
        $records = $this->logger()->takeRecords();
        $checkoutRequests = array_filter(
            $records,
            static fn (array $r) => ($r['context']['request_uri'] ?? '') === 'http://localhost/api/checkout',
        );
        self::assertCount(2, $checkoutRequests, 'Precondition: both checkout requests should have been logged.');

        foreach ($records as $record) {
            self::assertStringNotContainsString(
                PaymentSimulator::DECLINED_CARD,
                self::logText($record['message'], $record['context']),
                sprintf('The card number was logged (%s): %s', $record['level'], $record['message']),
            );
        }
    }

    // --- helpers ---

    private function createFixtures(): void
    {
        $seller = new Seller('Checkout Test Seller', '4.50', true);
        $productA = new Product('checkout-test-game-a', 'Checkout Test Game A', 'Test product.', 'https://example.com/a.jpg', 'Steam', 'GLOBAL', 2000, new \DateTimeImmutable('2024-01-01'));
        $productB = new Product('checkout-test-game-b', 'Checkout Test Game B', 'Test product.', 'https://example.com/b.jpg', 'Steam', 'GLOBAL', 3000, new \DateTimeImmutable('2024-01-01'));
        $this->offerA = new Offer($productA, $seller, 1500);
        $this->offerB = new Offer($productB, $seller, 2500);

        $this->user = new User('checkout-test@example.com');
        $this->user->setPassword('not-used'); // the test signs in with a token, not a password

        foreach ([$seller, $productA, $productB, $this->offerA, $this->offerB, $this->user] as $entity) {
            $this->em->persist($entity);
        }
        foreach (['TEST-A-0001', 'TEST-A-0002', 'TEST-A-0003'] as $code) {
            $this->em->persist(new GameKey($this->offerA, $code));
        }
        foreach (['TEST-B-0001', 'TEST-B-0002'] as $code) {
            $this->em->persist(new GameKey($this->offerB, $code));
        }
        $this->em->flush();

        $this->token = static::getContainer()->get(ApiTokenService::class)->issue($this->user)['token'];
    }

    /**
     * @param list<array{offerId: int|null, quantity: int}>                 $items
     * @param array{number: string, expiry: string, cvc: string, name: string} $card
     *
     * @return array<string, mixed> the decoded JSON response
     */
    private function postCheckout(array $items, array $card): array
    {
        $this->client->jsonRequest('POST', '/api/checkout', ['items' => $items, 'card' => $card], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->token,
        ]);

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }

    /** @return list<string> key codes delivered by an order */
    private function deliveredCodes(int $orderId): array
    {
        return $this->connection->fetchFirstColumn(
            'SELECT k.code FROM game_key k JOIN order_item i ON i.id = k.order_item_id WHERE i.order_id = ? ORDER BY k.code',
            [$orderId],
        );
    }

    private function countKeys(int $offerId, KeyStatus $status): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM game_key WHERE offer_id = ? AND status = ?', [$offerId, $status->value]);
    }

    /** @return array<string, int> row counts that any saved checkout would change */
    private function snapshot(): array
    {
        return array_map('intval', $this->connection->fetchAssociative(
            "SELECT (SELECT COUNT(*) FROM orders) AS orders,
                    (SELECT COUNT(*) FROM order_item) AS order_items,
                    (SELECT COUNT(*) FROM game_key WHERE status = 'sold') AS sold_keys",
        ));
    }

    private function logger(): InMemoryLogger
    {
        $logger = static::getContainer()->get('logger');
        self::assertInstanceOf(InMemoryLogger::class, $logger, 'config/services.yaml should set InMemoryLogger for tests.');

        return $logger;
    }

    /**
     * Everything a log line could print: the message, plain context values, and any exception's
     * message and stack trace (including the exceptions it wraps).
     *
     * @param array<mixed> $context
     */
    private static function logText(string $message, array $context): string
    {
        $parts = [$message];
        array_walk_recursive($context, static function (mixed $value) use (&$parts): void {
            if ($value instanceof \Throwable) {
                for ($e = $value; $e !== null; $e = $e->getPrevious()) {
                    $parts[] = $e->getMessage();
                    $parts[] = $e->getTraceAsString();
                }
            } elseif (\is_scalar($value) || $value instanceof \Stringable) {
                $parts[] = (string) $value;
            }
        });

        return implode("\n", $parts);
    }
}
