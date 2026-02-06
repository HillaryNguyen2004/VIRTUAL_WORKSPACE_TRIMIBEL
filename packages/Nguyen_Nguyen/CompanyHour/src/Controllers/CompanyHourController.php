<?php
namespace NguyenNguyen\CompanyHour\Controllers;

use Illuminate\Routing\Controller;
use NguyenNguyen\CompanyHour\Requests\StoreCompanyHourRequest;
use NguyenNguyen\CompanyHour\Services\CompanyHourService;
use NguyenNguyen\CompanyHour\Models\CompanyHour;

class CompanyHourController extends Controller
{
    protected $companyHourService;

    public function __construct(CompanyHourService $companyHourService)
    {
        $this->companyHourService = $companyHourService;
    }

    public function index()
    {
        $hour = $this->companyHourService->getFirst();
        return view('companyhour::index', compact('hour'));
    }

    public function create()
    {
        return view('companyhour::create');
    }

    public function store(StoreCompanyHourRequest $request)
    {
        $this->companyHourService->store($request->validated());
        return redirect()->route('admin.company_hours.index')->with('success', 'Company hour saved!');
    }

    public function edit()
    {
        $companyhour = $this->companyHourService->getFirst();
        return view('companyhour::edit', compact('companyhour'));
    }

    public function update(StoreCompanyHourRequest $request)
    {
        $this->companyHourService->update($request->validated());
        return redirect()->back()->with('success', 'Updated!');
    }

    public function destroy(CompanyHour $companyhour)
    {
        $this->companyHourService->delete($companyhour);
        return redirect()->route('admin.company_hours.index')->with('success', 'Deleted!');
    }
}
