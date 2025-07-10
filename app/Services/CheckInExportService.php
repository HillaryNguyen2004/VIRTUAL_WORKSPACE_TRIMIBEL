<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CheckInExportService
{
    
    public function getFilteredCheckIns(Request $request)
    {
        $query = DB::table('check_ins')
            ->orderByDesc('date')
            ->orderByDesc('check_in_time');

        if ($request->filled('username')) {
            $query->where('user_name', 'like', '%' . $request->username . '%');
        }

        if ($request->filled('date')) {
            $query->where('date', $request->date);
        }

        if ($request->filled('status')) {
            if ($request->status === 'late') {
                $query->where('is_late', true);
            } elseif ($request->status === 'on_time') {
                $query->where('is_late', false);
            }
        }

        return $query; // ✅ Return the query builder (no ->get())
    }


    public function generateExcel($checkIns)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $sheet->fromArray(['User Name', 'Date', 'Check In', 'Check Out', 'Status'], NULL, 'A1');

        $row = 2;
        foreach ($checkIns as $checkIn) {
            $sheet->setCellValue('A' . $row, $checkIn->user_name);
            $sheet->setCellValue('B' . $row, $checkIn->date);
            $sheet->setCellValue('C' . $row, $checkIn->check_in_time);
            $sheet->setCellValue('D' . $row, $checkIn->check_out_time ?? 'N/A');
            $sheet->setCellValue('E' . $row, $checkIn->is_late ? 'Late' : 'On Time');
            $row++;
        }

        $filename = 'checkin_logs_' . now()->format('Ymd_His') . '.xlsx';
        $tempPath = tempnam(sys_get_temp_dir(), $filename);
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return [
            'file' => $tempPath,
            'name' => $filename
        ];
    }
}
