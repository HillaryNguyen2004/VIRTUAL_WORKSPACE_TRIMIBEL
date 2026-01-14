<?php
// App\Repositories\CompanyHoursRepository.php
namespace App\Repositories;

use App\Models\CompanyHour;

class CompanyHoursRepository
{
    public function getCompanyHours()
    {
        return CompanyHour::first();
    }
}