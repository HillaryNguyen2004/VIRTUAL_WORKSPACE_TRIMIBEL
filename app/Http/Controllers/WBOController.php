<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WBOController extends Controller
{
    /**
     * Show the WBO choice page (create new or open existing)
     */
    public function index()
    {
        return view('wbo.index');
    }

    /**
     * Create a new whiteboard session
     */
    public function create(Request $request)
    {
        // Generate a unique board ID
        $boardId = Str::uuid();
        
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
        // Using open-source WBO (WhiteBoard Online)
        // You can replace this with your own WBO instance if you're hosting it locally
        $wboUrl = $this->generateWBOUrl($boardId);

        return view('wbo.board', [
            'boardId' => $boardId,
            'wboUrl' => $wboUrl
        ]);
    }

    /**
     * Generate the WBO URL
     * You can customize this based on your WBO deployment
     */
    private function generateWBOUrl($boardId)
    {
        // Using the public WBO instance
        // For production, you should host your own WBO instance
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
}
