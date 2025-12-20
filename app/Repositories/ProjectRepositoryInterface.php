<?php

namespace App\Repositories;

use App\Models\Project;

interface ProjectRepositoryInterface
{
    public function create(array $data): Project;
    // public function find(int $id): ?Project;
    // public function update(Project $project, array $data): Project;
    // public function delete(Project $project): bool;
}
