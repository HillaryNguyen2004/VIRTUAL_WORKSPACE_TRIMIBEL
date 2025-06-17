<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }
    public function user()
    {
        $user = Auth::user();

    // Get team leader (staff)
    $teamLeader = null;
    $teamMembers = collect();
    if ($user->team_leader_id !== null) {
        $teamLeader = User::find($user->team_leader_id);

        // Get other users under the same team leader
        $teamMembers = User::where('team_leader_id', $user->team_leader_id)
                           ->where('id', '!=', $user->id)
                           ->get();
    }

    // Get tasks assigned to this user
    // $tasks = Task::where('assigned_to', $user->id)->get();

    return view('dashboard', compact('user', 'teamLeader', 'teamMembers'));
    }
}
