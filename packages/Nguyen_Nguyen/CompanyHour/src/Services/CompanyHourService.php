<?php
namespace NguyenNguyen\CompanyHour\Services;

use NguyenNguyen\CompanyHour\Repositories\CompanyHourRepository;

class CompanyHourService
{
    protected $repo;

    public function __construct(CompanyHourRepository $repo)
    {
        $this->repo = $repo;
    }

    public function getFirst()
    {
        return $this->repo->first();
    }

    public function getFirstOrFail()
    {
        return $this->repo->firstOrFail();
    }

    public function store(array $data)
    {
        return $this->repo->updateOrCreate($data);
    }

    public function update(array $data)
    {
        $companyHour = $this->repo->firstOrFail();
        \Log::info('Updating company hour', $data);
        return $this->repo->update($companyHour, $data);
    }

    public function destroy()
    {
        $companyHour = $this->repo->firstOrFail();
        return $this->repo->delete($companyHour);
    }
}
