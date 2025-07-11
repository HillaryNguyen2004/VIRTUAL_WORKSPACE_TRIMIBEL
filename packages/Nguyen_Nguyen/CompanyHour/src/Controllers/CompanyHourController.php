<?php
namespace NguyenNguyen\CompanyHour\Controllers;

use NguyenNguyen\CompanyHour\Models\CompanyHour;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use NguyenNguyen\CompanyHour\Requests\StoreCompanyHourRequest;

class CompanyHourController extends Controller
{
    public function index()
    {
        $hours = CompanyHour::all();
        return view('companyhour::index', compact('hours'));
    }

    public function create()
    {
        return view('companyhour::create');
    }

    public function store(StoreCompanyHourRequest $request)
    {
        // CompanyHour::truncate();
        // CompanyHour::create($request->validated());
        CompanyHour::updateOrCreate([], $request->validated());
        return redirect()->route('companyhour.index')->with('success', 'Created!');
    }

    public function edit(CompanyHour $companyhour)
    {
        return view('companyhour::edit', compact('companyhour'));
    }

    public function update(StoreCompanyHourRequest $request, CompanyHour $companyhour)
    {
        $companyhour->update($request->validated());
        return redirect()->route('companyhour.index')->with('success', 'Updated!');
    }

    public function destroy(CompanyHour $companyhour)
    {
        $companyhour->delete();
        return redirect()->route('companyhour.index')->with('success', 'Deleted!');
    }
}
