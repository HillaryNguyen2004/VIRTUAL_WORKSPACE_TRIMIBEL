<?php
namespace App\Repositories;

interface CheckInRepositoryInterface
{
    public function getTodayCheckIn(string $userName);
    public function updateCheckOut(int $id, string $time): void;
}
