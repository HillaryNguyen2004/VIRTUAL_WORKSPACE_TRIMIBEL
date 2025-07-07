<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserExportService;

class UserExportController extends Controller
{
    protected $exportService;

    public function __construct(UserExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    public function exportExcel(Request $request)
    {
        $users = $this->exportService->getFilteredUsers($request);
        $export = $this->exportService->generateExcel($users);

        return response()->download($export['file'], $export['name'])->deleteFileAfterSend(true);
    }
}
