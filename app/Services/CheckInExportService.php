<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;
use App\Models\CheckIn;


class CheckInExportService
{
    
    public function getFilteredCheckIns(Request $request)
    {
        $query = CheckIn::query()
            ->select('check_ins.*') // Ensure we select from check_ins table
            ->orderByDesc('date')
            ->orderByDesc('check_in_time');

        if ($request->filled('username')) {
            $query->where('user_name', 'like', '%' . $request->username . '%');
        }

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
                // Join with company_hours to check if late
                $query->join('company_hours', function($join) {
                    // We assume there's only one company_hours record
                    $join->on('company_hours.id', '=', DB::raw('(SELECT MIN(id) FROM company_hours)'));
                })
                ->whereRaw("
                    check_ins.check_in_time IS NOT NULL 
                    AND STR_TO_DATE(CONCAT(check_ins.date, ' ', check_ins.check_in_time), '%Y-%m-%d %H:%i:%s') > 
                        STR_TO_DATE(CONCAT(check_ins.date, ' ', company_hours.start_at), '%Y-%m-%d %H:%i:%s') + INTERVAL 5 MINUTE
                ");
            } elseif ($request->status === 'on_time') {
                // Join with company_hours to check if on time
                $query->join('company_hours', function($join) {
                    $join->on('company_hours.id', '=', DB::raw('(SELECT MIN(id) FROM company_hours)'));
                })
                ->whereRaw("
                    check_ins.check_in_time IS NOT NULL 
                    AND STR_TO_DATE(CONCAT(check_ins.date, ' ', check_ins.check_in_time), '%Y-%m-%d %H:%i:%s') <= 
                        STR_TO_DATE(CONCAT(check_ins.date, ' ', company_hours.start_at), '%Y-%m-%d %H:%i:%s') + INTERVAL 5 MINUTE
                ");
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
