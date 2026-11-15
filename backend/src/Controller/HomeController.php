<?php

namespace App\Controller;

use App\Dto\HomeBlockContent;
use App\Home\HomeBlocks;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The homepage, in two steps (PLAN.md §2): the layout, then each block on its own request.
 * A block whose provider fails answers with an error, and the other blocks are unaffected.
 */
final class HomeController
{
    public function __construct(
        private readonly HomeBlocks $home,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** GET /api/home → { blocks: [{ id, type, title }] } */
    #[Route('/api/home', name: 'api_home', methods: ['GET'])]
    public function layout(): JsonResponse
    {
        return new JsonResponse(['blocks' => $this->home->layout()]);
    }

    /**
     * GET /api/home/blocks/{id} → { id, type, title, items }, 404 not_found, or 503 block_unavailable.
     * The requirement makes /api/home/blocks/abc a 404 too (no route matches), like {id:int} in ASP.NET.
     */
    #[Route('/api/home/blocks/{id}', name: 'api_home_block', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function block(int $id): JsonResponse
    {
        $block = $this->home->find($id);
        if ($block === null) {
            return new JsonResponse(['error' => ['code' => 'not_found', 'message' => 'Block not found.']], 404);
        }

        try {
            $items = $this->home->provide($block);
        } catch (\Throwable $e) {
            // One failing data source must not take the homepage down. The details go to the log only;
            // the client gets a fixed message, so no internal error text leaks.
            $this->logger->error('Homepage block {id} ({type}) failed.', [
                'id' => $id,
                'type' => $block->getType(),
                'exception' => $e,
            ]);

            return new JsonResponse(
                ['error' => ['code' => 'block_unavailable', 'message' => "This section couldn't be loaded."]],
                503,
            );
        }

        return new JsonResponse(HomeBlockContent::fromEntity($block, $items));
    }
}
