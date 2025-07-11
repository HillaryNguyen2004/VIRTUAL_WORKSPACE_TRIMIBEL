<?php
namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckInRepository implements CheckInRepositoryInterface
{
    public function getTodayCheckIn(string $userName)
    {
        return DB::table('check_ins')
            ->where('user_name', $userName)
            ->where('date', Carbon::now('Asia/Ho_Chi_Minh')->toDateString())
            ->first();
    }

    public function updateCheckOut(int $id, string $time): void
    {
        DB::table('check_ins')->where('id', $id)->update([
            'check_out_time' => $time,
            'updated_at' => Carbon::now('Asia/Ho_Chi_Minh')
        ]);
    }
}
