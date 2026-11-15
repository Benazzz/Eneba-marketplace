<?php

namespace App\Home;

use App\Dto\BannerSlide;
use App\Dto\HomeBlockSummary;
use App\Dto\ProductCard;
use App\Entity\HomeBlock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * The homepage: its layout (the `home_block` table) and the provider that fills each block (PLAN.md §2).
 */
final class HomeBlocks
{
    /**
     * @param iterable<BlockProviderInterface> $providers every service tagged by BlockProviderInterface,
     *                                                    highest tag priority first. Created lazily, as they're iterated.
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        #[AutowireIterator(BlockProviderInterface::TAG)]
        private readonly iterable $providers,
    ) {
    }

    /** @return list<HomeBlockSummary> enabled blocks, in display order */
    public function layout(): array
    {
        $blocks = $this->em->getRepository(HomeBlock::class)->findBy(['enabled' => true], ['position' => 'ASC']);

        return array_map(HomeBlockSummary::fromEntity(...), $blocks);
    }

    /** Null when no enabled block has this id. */
    public function find(int $id): ?HomeBlock
    {
        return $this->em->getRepository(HomeBlock::class)->findOneBy(['id' => $id, 'enabled' => true]);
    }

    /**
     * The block's items, from the first provider that supports its type.
     * Throws whatever the provider throws, or LogicException when no provider knows the type.
     *
     * @return list<BannerSlide>|list<ProductCard>
     */
    public function provide(HomeBlock $block): array
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($block->getType())) {
                return $provider->provide($block);
            }
        }

        throw new \LogicException(\sprintf('No homepage block provider supports the type "%s".', $block->getType()));
    }
}
