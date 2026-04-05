<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenBoardRequest;
use App\Services\WBOBoardService;

class WBOController extends Controller
{
    public function __construct(
        private WBOBoardService $service
    ) {
    }

    /**
     * Show the WBO choice page (create new or open existing)
     */
    public function index()
    {
        $recentBoards = $this->service->getRecentBoards();

        return view('wbo.index', compact('recentBoards'));
    }

    /**
     * Create a new whiteboard session
     */
    public function create()
    {
        $boardId = $this->service->createBoard();

        return redirect()->route('wbo.board', ['boardId' => $boardId]);
    }

    /**
     * Open an existing whiteboard session
     */
    public function open(OpenBoardRequest $request)
    {
        $boardId = $request->validated()['board_id'];

        if (!$this->service->openBoard($boardId)) {
            return redirect()->route('wbo.index')
                ->withErrors(['board_id' => 'Invalid board ID format']);
        }

        return redirect()->route('wbo.board', ['boardId' => $boardId]);
    }

    /**
     * Display the whiteboard board
     */
    public function board(string $boardId)
    {
        if (!$this->service->validateBoardId($boardId)) {
            abort(404, 'Invalid board ID');
        }

        $data = $this->service->getBoardData($boardId);

        return view('wbo.board', $data);
    }

    /**
     * Save the whiteboard data
     */
    public function save(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|string',
            'board_data' => 'required|string',
        ]);

        $this->service->saveBoard($validated['board_id'], $validated['board_data']);

        return response()->json(['message' => 'Board saved successfully']);
    }
}
