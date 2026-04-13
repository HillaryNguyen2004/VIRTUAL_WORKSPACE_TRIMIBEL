<?php

namespace App\Repositories;

use App\Models\WBOBoard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WBOBoardRepository
{
    public function __construct(private WBOBoard $model)
    {
    }

    /**
     * Get board history for current user
     */
    public function getBoardHistory(): array
    {
        return $this->model
            ->where('user_id', Auth::id())
            ->orderBy('last_accessed_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Add a board to user's history, or create it if it doesn't exist.
     */
    public function addToHistory(string $boardId, string $action = 'opened'): void
    {
        $this->model->updateOrCreate(
            ['board_id' => $boardId, 'user_id' => Auth::id()],
            ['last_accessed_at' => now()]
        );
    }

    /**
     * Get board data from the database.
     */
    public function getBoardData(string $boardId): ?string
    {
        $board = $this->model->where('board_id', $boardId)->first();
        return $board ? $board->board_data : null;
    }

    /**
     * Update board data in the database.
     */
    public function updateBoardData(string $boardId, string $data): void
    {
        $this->model->where('board_id', $boardId)->update(['board_data' => $data]);
    }

    /**
     * Validate UUID format
     */
    public function isValidUUID(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Generate a unique board ID (UUID)
     */
    public function generateBoardId(): string
    {
        return Str::uuid();
    }
}

