<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Phase;
use App\Models\Project;

class PhaseController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $project->phases()->create($validated);

        return back()->with('success', __('messages.phase_created'));
    }

    public function update(Request $request, Phase $phase)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $phase->update($validated);

        return back()->with('success', __('messages.phase_updated'));
    }

    public function destroy(Phase $phase)
    {
        $phase->delete();

        return back()->with('success', __('messages.phase_deleted'));
    }
}
