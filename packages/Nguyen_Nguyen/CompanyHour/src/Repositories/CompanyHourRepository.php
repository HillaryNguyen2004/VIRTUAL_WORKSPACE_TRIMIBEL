<?php
namespace NguyenNguyen\CompanyHour\Repositories;

use NguyenNguyen\CompanyHour\Models\CompanyHour;

class CompanyHourRepository
{
    public function first()
    {
        return CompanyHour::first();
    }

    public function firstOrFail()
    {
        return CompanyHour::firstOrFail();
    }

    public function updateOrCreate(array $data)
    {
        $existing = CompanyHour::first();

        if ($existing) {
            $existing->update($data);
            return $existing;
        }

        return CompanyHour::create($data);
    }

    public function update(CompanyHour $companyHour, array $data)
    {
        return $companyHour->update($data);
    }

    public function delete(CompanyHour $companyHour)
    {
        return $companyHour->delete();
    }
}
