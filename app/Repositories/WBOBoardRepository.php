<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WBOBoardRepository
{
    private const HISTORY_SESSION_KEY = 'wbo_board_history';
    private const MAX_HISTORY_ITEMS = 10;

    /**
     * Get board history for current user
     */
    public function getBoardHistory(): array
    {
        $userId = Auth::id();
        $sessionKey = $this->getSessionKey($userId);
        
        return session()->get($sessionKey, []);
    }

    /**
     * Add a board to user's history
     */
    public function addToHistory(string $boardId, string $action = 'opened'): void
    {
        $userId = Auth::id();
        $sessionKey = $this->getSessionKey($userId);
        
        $history = session()->get($sessionKey, []);
        
        // Remove if already exists to avoid duplicates
        $history = array_filter($history, function($item) use ($boardId) {
            return $item['id'] !== $boardId;
        });
        
        // Add to beginning
        array_unshift($history, [
            'id' => $boardId,
            'action' => $action,
            'accessed_at' => now()->toDateTimeString()
        ]);
        
        // Keep only last N items
        $history = array_slice($history, 0, self::MAX_HISTORY_ITEMS);
        
        session()->put($sessionKey, $history);
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

    /**
     * Get session key for user
     */
    private function getSessionKey(string $userId): string
    {
        return self::HISTORY_SESSION_KEY . "_{$userId}";
    }
}
