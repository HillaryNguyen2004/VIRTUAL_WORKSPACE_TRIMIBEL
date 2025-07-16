<?php

namespace NguyenNguyen\CompanyHour\Repositories;

use NguyenNguyen\CompanyHour\Models\CompanyHour;

class CompanyHourRepository implements CompanyHourRepositoryInterface
{
    public function getFirst()
    {
        return CompanyHour::first();
    }

    public function updateOrCreate(array $data)
    {
        return CompanyHour::updateOrCreate(
            ['id' => CompanyHour::first()?->id],
            $data
        );
    }

    public function updateFirst(array $data)
    {
        $companyhour = CompanyHour::firstOrFail();
        $companyhour->update($data);
        return $companyhour;
    }

    public function delete($companyhour)
    {
        return $companyhour->delete();
    }
}
