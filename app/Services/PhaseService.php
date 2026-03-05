<?php

namespace App\Services;

use App\Models\Phase;
use App\Models\Project;
use App\Repositories\PhaseRepository;

class PhaseService
{
    public function __construct(
        private PhaseRepository $repository
    ) {
    }

    /**
     * Create a new phase for a project
     */
    public function createPhase(Project $project, array $data): Phase
    {
        return $this->repository->create($project, $data);
    }

    /**
     * Update an existing phase
     */
    public function updatePhase(Phase $phase, array $data): Phase
    {
        return $this->repository->update($phase, $data);
    }

    /**
     * Delete a phase
     */
    public function deletePhase(Phase $phase): bool
    {
        return $this->repository->delete($phase);
    }
}
