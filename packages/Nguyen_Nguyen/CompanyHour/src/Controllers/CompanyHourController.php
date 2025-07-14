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
        // $hours = CompanyHour::all();
        $hour = CompanyHour::first();
        return view('companyhour::index', compact('hour'));
    }

    public function create()
    {
        return view('companyhour::create');
    }

    
    public function store(StoreCompanyHourRequest $request)
    {
        // Always update the first row or create it if none exists
        CompanyHour::updateOrCreate(
            ['id' => CompanyHour::first()?->id], // condition
            $request->validated()               // values to update/create
        );

        return redirect()->route('companyhour.index')->with('success', 'Company hour saved!');
    }


    // public function edit(CompanyHour $companyhour)
    // {
    //     return view('companyhour::edit', compact('companyhour'));
    // }

    // public function update(StoreCompanyHourRequest $request, CompanyHour $companyhour)
    // {
    //     $companyhour->update($request->validated());
    //     return redirect()->route('companyhour.index')->with('success', 'Updated!');
    // }


    public function edit()
    {
        $companyhour = CompanyHour::firstOrFail();
        return view('companyhour::edit', compact('companyhour'));
    }

    public function update(StoreCompanyHourRequest $request)
    {
        $companyhour = CompanyHour::firstOrFail();
        $companyhour->update($request->validated());
        return redirect()->route('companyhour.index')->with('success', 'Updated!');
    }

    public function destroy(CompanyHour $companyhour)
    {
        $companyhour->delete();
        return redirect()->route('companyhour.index')->with('success', 'Deleted!');
    }
}
