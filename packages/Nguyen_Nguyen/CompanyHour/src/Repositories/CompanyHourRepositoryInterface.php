<?php

namespace NguyenNguyen\CompanyHour\Repositories;

interface CompanyHourRepositoryInterface
{
    public function getFirst();
    public function updateOrCreate(array $data);
    public function updateFirst(array $data);
    public function delete($companyhour);
}
