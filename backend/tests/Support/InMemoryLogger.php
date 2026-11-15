<?php

namespace App\Tests\Support;

use Psr\Log\AbstractLogger;

/**
 * The app's logger in the test environment (config/services.yaml, when@test). It keeps every record in
 * memory and prints nothing, so tests can check what *would* have been logged (CheckoutServiceTest makes
 * sure no card number is ever logged).
 */
final class InMemoryLogger extends AbstractLogger
{
    /** @var list<array{level: string, message: string, context: array<mixed>}> */
    private array $records = [];

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => (string) $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * Returns the records so far and forgets them.
     *
     * @return list<array{level: string, message: string, context: array<mixed>}>
     */
    public function takeRecords(): array
    {
        $records = $this->records;
        $this->records = [];

        return $records;
    }
}
