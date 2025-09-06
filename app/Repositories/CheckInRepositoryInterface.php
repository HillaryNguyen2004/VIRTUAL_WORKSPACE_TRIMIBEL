<?php
namespace App\Repositories;

interface CheckInRepositoryInterface
{
    // public function getTodayCheckIn(string $userName);
    public function updateCheckOut(int $id, string $time): void;
    // public function hasCheckedInToday(string $username, string $date): bool;

    public function insertCheckIn(array $data): void;
    public function getTodayCheckIn(string $username);
    public function hasCheckedInToday(string $username, string $date);

}
