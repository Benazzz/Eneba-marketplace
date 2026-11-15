<?php

namespace App\Tests\Controller;

use App\Tests\Support\FailingBlockProvider;
use App\Tests\Support\InMemoryLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * The homepage endpoints (PLAN.md §2, §4): GET /api/home and GET /api/home/blocks/{id}.
 * Read-only requests against the seeded test database (see ProductSearchTest for the setup).
 */
final class HomeTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Keep one kernel (and so one logger) for every request in a test.
        $this->client->disableReboot();
    }

    protected function tearDown(): void
    {
        FailingBlockProvider::$failType = null;
        parent::tearDown();
    }

    public function testLayoutListsTheSeededBlocksInOrder(): void
    {
        $layout = $this->getJson('/api/home');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['hero_banner', 'best_sellers', 'deals', 'new_releases'],
            array_column($layout['blocks'], 'type'),
        );
    }

    public function testEveryBlockReturnsItsItems(): void
    {
        foreach ($this->blockIdsByType() as $type => $id) {
            $block = $this->getJson("/api/home/blocks/$id");

            self::assertResponseIsSuccessful($type);
            self::assertSame($type, $block['type']);
            self::assertNotEmpty($block['items'], $type);

            if ($type === 'hero_banner') {
                foreach ($block['items'] as $banner) {
                    self::assertNotEmpty($banner['imageUrl']);
                    self::assertStringStartsWith('/product/', $banner['linkUrl']);
                }
                continue;
            }

            // Product rows: at most 10 cards, and only products that can be bought right now.
            self::assertLessThanOrEqual(10, \count($block['items']), $type);
            foreach ($block['items'] as $card) {
                self::assertNotNull($card['fromPriceCents'], "$type: {$card['slug']}");
            }
        }
    }

    /** If one provider throws, only its block fails. The layout and the other blocks still work. */
    public function testAFailingProviderOnlyBreaksItsOwnBlock(): void
    {
        $ids = $this->blockIdsByType();
        FailingBlockProvider::$failType = 'deals';

        $deals = $this->getJson("/api/home/blocks/{$ids['deals']}");
        self::assertResponseStatusCodeSame(503);
        self::assertSame('block_unavailable', $deals['error']['code']);
        // The exception's own text stays on the server.
        self::assertStringNotContainsString('Simulated', $deals['error']['message']);

        self::assertCount(4, $this->getJson('/api/home')['blocks']);
        foreach (['hero_banner', 'best_sellers', 'new_releases'] as $type) {
            $block = $this->getJson("/api/home/blocks/{$ids[$type]}");
            self::assertResponseIsSuccessful($type);
            self::assertNotEmpty($block['items'], $type);
        }

        // The failure was logged, with the exception for debugging.
        $errors = array_values(array_filter(
            $this->logger()->takeRecords(),
            static fn (array $r): bool => $r['level'] === 'error',
        ));
        self::assertCount(1, $errors);
        self::assertSame('deals', $errors[0]['context']['type']);
        self::assertInstanceOf(\RuntimeException::class, $errors[0]['context']['exception']);
    }

    public function testUnknownBlockIsNotFound(): void
    {
        $this->getJson('/api/home/blocks/999999');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/api/home/blocks/abc');
        self::assertResponseStatusCodeSame(404);
    }

    /** @return array<string, int> block type → id */
    private function blockIdsByType(): array
    {
        $blocks = $this->getJson('/api/home')['blocks'];

        return array_combine(array_column($blocks, 'type'), array_column($blocks, 'id'));
    }

    private function logger(): InMemoryLogger
    {
        $logger = static::getContainer()->get('logger');
        self::assertInstanceOf(InMemoryLogger::class, $logger);

        return $logger;
    }

    /** @return array<string, mixed> */
    private function getJson(string $url): array
    {
        $this->client->request('GET', $url);

        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
    }
}
