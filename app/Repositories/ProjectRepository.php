<?php

namespace App\Repositories;

use App\Models\Project;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $model)
    {
        parent::__construct($model);
    }

    // public function find(int $id): ?Project
    // {
    //     return $this->model->find($id);
    // }

    public function find($id): ?Project
    {
        /** @var \App\Models\Project|null $task */
        $task = parent::find($id);
        return $task;
    }

    public function create(array $data): Project
    {
        return $this->model->create($data);
    }

    // public function updateProject(Project $project, array $data): Project
    // {
    //     $project->update($data);
    //     return $project;
    // }
    
    // public function update(Project $project, array $data): Project
    // {
    //     $project->update($data);
    //     return $project;
    // }

    // public function delete(Project $project): bool
    // {
    //     return (bool) $project->delete();
    // }
}
