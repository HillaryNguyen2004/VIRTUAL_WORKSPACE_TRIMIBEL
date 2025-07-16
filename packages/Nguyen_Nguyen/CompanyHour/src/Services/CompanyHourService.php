<?php

namespace NguyenNguyen\CompanyHour\Services;

use NguyenNguyen\CompanyHour\Repositories\CompanyHourRepositoryInterface;

class CompanyHourService
{
    protected $repository;

    public function __construct(CompanyHourRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getFirst()
    {
        return $this->repository->getFirst();
    }

    public function store(array $data)
    {
        return $this->repository->updateOrCreate($data);
    }

    public function update(array $data)
    {
        return $this->repository->updateFirst($data);
    }

    public function delete($companyhour)
    {
        return $this->repository->delete($companyhour);
    }
}
