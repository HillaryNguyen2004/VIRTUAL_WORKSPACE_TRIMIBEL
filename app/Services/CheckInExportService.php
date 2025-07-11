<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;


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

        // if ($request->filled('date')) {
        //     $query->where('date', $request->date);
        // }

        // ✅ Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
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

        // Updated headers
        $sheet->fromArray(['#', 'User Name', 'Date', 'Check In Time', 'Check Out Time', 'Working Hours'], NULL, 'A1');

        $row = 2;
        $index = 1;

        foreach ($checkIns as $checkIn) {
            $checkInTime = $checkIn->check_in_time ?? '-';
            $checkOutTime = $checkIn->check_out_time ?? '-';

            // Calculate working hours if both times are available
            if ($checkIn->check_in_time && $checkIn->check_out_time) {
                $checkInCarbon = Carbon::parse($checkIn->check_in_time);
                $checkOutCarbon = Carbon::parse($checkIn->check_out_time);
                $workingHours = $checkOutCarbon->diff($checkInCarbon)->format('%H:%I') . ' hrs';
            } elseif ($checkIn->check_in_time && !$checkIn->check_out_time) {
                $workingHours = 'Checked In';
            } else {
                $workingHours = 'Not Checked In';
            }

            $sheet->setCellValue('A' . $row, $index);
            $sheet->setCellValue('B' . $row, $checkIn->user_name);
            $sheet->setCellValue('C' . $row, $checkIn->date);
            $sheet->setCellValue('D' . $row, $checkInTime);
            $sheet->setCellValue('E' . $row, $checkOutTime);
            $sheet->setCellValue('F' . $row, $workingHours);

            $row++;
            $index++;
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
