<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class WBOController extends Controller
{
    private const HISTORY_SESSION_KEY = 'wbo_board_history';
    private const MAX_HISTORY_ITEMS = 10;

    /**
     * Show the WBO choice page (create new or open existing)
     */
    public function index()
    {
        // Get board history for current user
        $history = $this->getBoardHistory();

        return view('wbo.index', [
            'recentBoards' => $history
        ]);
    }

    /**
     * Create a new whiteboard session
     */
    public function create(Request $request)
    {
        // Generate a unique board ID
        $boardId = Str::uuid();
        
        // Store in history
        $this->addToHistory($boardId, 'created');
        
        // Redirect to the board with the new ID
        return redirect()->route('wbo.board', ['boardId' => $boardId]);
    }

    /**
     * Open an existing whiteboard session
     */
    public function open(Request $request)
    {
        $request->validate([
            'board_id' => 'required|string|min:36|max:36'
        ]);

        $boardId = $request->input('board_id');
        
        // Validate UUID format before storing
        if (!$this->isValidUUID($boardId)) {
            return redirect()->route('wbo.index')
                ->withErrors(['board_id' => 'Invalid board ID format']);
        }
        
        // Store in history
        $this->addToHistory($boardId, 'opened');
        
        return redirect()->route('wbo.board', ['boardId' => $boardId]);
    }

    /**
     * Display the whiteboard board
     */
    public function board($boardId)
    {
        // Validate board ID format (UUID)
        if (!$this->isValidUUID($boardId)) {
            abort(404, 'Invalid board ID');
        }

        // Generate the WBO URL
        $wboUrl = $this->generateWBOUrl($boardId);

        return view('wbo.board', [
            'boardId' => $boardId,
            'wboUrl' => $wboUrl
        ]);
    }

    /**
     * Generate the WBO URL
     */
    private function generateWBOUrl($boardId)
    {
        $wboHost = env('WBO_HOST', 'https://wbo.ophir.dev');
        return "{$wboHost}/boards/{$boardId}";
    }

    /**
     * Validate UUID format
     */
    private function isValidUUID($uuid)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Add a board to user's history
     */
    private function addToHistory($boardId, $action = 'opened')
    {
        $userId = Auth::id();
        $sessionKey = self::HISTORY_SESSION_KEY . "_{$userId}";
        
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
     * Get board history for current user
     */
    private function getBoardHistory()
    {
        $userId = Auth::id();
        $sessionKey = self::HISTORY_SESSION_KEY . "_{$userId}";
        
        return session()->get($sessionKey, []);
    }
}
