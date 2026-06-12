<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePhaseRequest;
use App\Http\Requests\UpdatePhaseRequest;
use App\Models\Phase;
use App\Models\Project;
use App\Services\PhaseService;

class PhaseController extends Controller
{
    public function __construct(
        private PhaseService $service
    ) {
    }

    public function store(StorePhaseRequest $request, Project $project)
    {
        $this->service->createPhase($project, $request->validated());

        return back()->with('success', __('messages.phase_created'));
    }

    public function update(UpdatePhaseRequest $request, Phase $phase)
    {
        $this->service->updatePhase($phase, $request->validated());

        return back()->with('success', __('messages.phase_updated'));
    }

    public function destroy(Phase $phase)
    {
        $this->service->deletePhase($phase);

        return back()->with('success', __('messages.phase_deleted'));
    }
}
