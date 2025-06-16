<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public static function dashboardRoute()
    {
        if (!Auth::check()) {
            return route('login');
        }

        $role = Auth::user()->roles;

        if ($role === 'admin') {
            return route('admin.dashboard');
        } elseif ($role === 'staff') {
            return route('staff.dashboard');
        } else {
            return route('user.dashboard');
        }
    }

}
