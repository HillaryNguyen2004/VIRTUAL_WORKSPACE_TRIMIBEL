<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    public function index()
    {
        return view('super_admin.dashboard');
    }
}
