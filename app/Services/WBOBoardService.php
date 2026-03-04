<?php

namespace App\Services;

use App\Repositories\WBOBoardRepository;

class WBOBoardService
{
    public function __construct(
        private WBOBoardRepository $repository
    ) {
    }

    /**
     * Create a new whiteboard session
     */
    public function createBoard(): string
    {
        $boardId = $this->repository->generateBoardId();
        $this->repository->addToHistory($boardId, 'created');
        
        return $boardId;
    }

    /**
     * Open an existing whiteboard session
     */
    public function openBoard(string $boardId): bool
    {
        if (!$this->repository->isValidUUID($boardId)) {
            return false;
        }
        
        $this->repository->addToHistory($boardId, 'opened');
        
        return true;
    }

    /**
     * Get board data for display
     */
    public function getBoardData(string $boardId): array
    {
        return [
            'boardId' => $boardId,
            'wboUrl' => $this->generateWBOUrl($boardId)
        ];
    }

    /**
     * Get recent boards for current user
     */
    public function getRecentBoards(): array
    {
        return $this->repository->getBoardHistory();
    }

    /**
     * Validate board ID
     */
    public function validateBoardId(string $boardId): bool
    {
        return $this->repository->isValidUUID($boardId);
    }

    /**
     * Generate the WBO URL
     */
    public function generateWBOUrl(string $boardId): string
    {
        $wboHost = env('WBO_HOST', 'https://wbo.ophir.dev');
        return "{$wboHost}/boards/{$boardId}";
    }
}
