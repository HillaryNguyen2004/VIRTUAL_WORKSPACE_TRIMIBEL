<?php
namespace App\Repositories;
use App\Models\CheckIn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CheckInRepository implements CheckInRepositoryInterface
{
    // public function getTodayCheckIn(string $userName)
    // {
    //     return DB::table('check_ins')
    //         ->where('user_name', $userName)
    //         ->where('date', Carbon::now('Asia/Ho_Chi_Minh')->toDateString())
    //         ->first();
    // }

    // public function updateCheckOut(int $id, string $time): void
    // {
    //     DB::table('check_ins')->where('id', $id)->update([
    //         'check_out_time' => $time,
    //         'updated_at' => Carbon::now('Asia/Ho_Chi_Minh')
    //     ]);
    // }

    // public function hasCheckedInToday(string $username, string $date): bool
    // {
    //     return DB::table('check_ins')
    //         ->where('user_name', $username)
    //         ->where('date', $date)
    //         ->exists();
    // }

    // public function insertCheckIn(array $data): void
    // {
    //     DB::table('check_ins')->insert($data);
    // }
    // public function getRecentCheckIns(int $limit = 3)
    // {
    //     return DB::table('check_ins')
    //         ->orderBy('date', 'desc')
    //         ->orderBy('check_in_time', 'desc')
    //         ->limit($limit)
    //         ->get();
    // }
     public function getTodayCheckIn(string $userName)
    {
        return CheckIn::where('user_name', $userName)
            ->where('date', Carbon::now('Asia/Ho_Chi_Minh')->toDateString())
            ->first();
    }

    public function updateCheckOut(int $id, string $time): void
    {
        CheckIn::where('id', $id)->update([
            'check_out_time' => $time,
            'updated_at' => Carbon::now('Asia/Ho_Chi_Minh'),
        ]);
    }

    public function hasCheckedInToday(string $username, string $date): bool
    {
        return CheckIn::where('user_name', $username)
            ->where('date', $date)
            ->exists();
    }

    public function insertCheckIn(array $data): void
    {
        CheckIn::create($data);
    }

    public function getRecentCheckIns(int $limit = 3)
    {
        return CheckIn::orderBy('date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->limit($limit)
            ->get();
    }
}
