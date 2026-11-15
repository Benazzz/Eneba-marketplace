<?php

namespace App\Tests\Support;

use App\Entity\HomeBlock;
use App\Home\BlockProviderInterface;

/**
 * A homepage block provider that only exists in the test environment (config/services.yaml, when@test).
 * It has a higher tag priority than the real providers, so HomeBlocks asks it first. While $failType is
 * set, it claims blocks of that type and throws, as if that block's data source were down.
 * With $failType null it supports nothing, and the real providers answer as usual.
 */
final class FailingBlockProvider implements BlockProviderInterface
{
    public static ?string $failType = null;

    public function supports(string $type): bool
    {
        return $type === self::$failType;
    }

    public function provide(HomeBlock $block): array
    {
        throw new \RuntimeException('Simulated failure of the "'.$block->getType().'" data source.');
    }
}
