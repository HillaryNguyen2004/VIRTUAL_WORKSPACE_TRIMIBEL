<?php

namespace App\Repositories;

use App\Models\Phase;
use App\Models\Project;

class PhaseRepository
{
    /**
     * Create a new phase for a project
     */
    public function create(Project $project, array $data): Phase
    {
        return $project->phases()->create($data);
    }

    /**
     * Update a phase
     */
    public function update(Phase $phase, array $data): Phase
    {
        $phase->update($data);
        return $phase;
    }

    /**
     * Delete a phase
     */
    public function delete(Phase $phase): bool
    {
        return $phase->delete();
    }
}
