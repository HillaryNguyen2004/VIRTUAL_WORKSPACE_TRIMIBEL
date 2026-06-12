<?php

namespace Tests\Unit;

use App\Models\Phase;
use App\Models\Project;
use App\Repositories\PhaseRepository;
use App\Services\PhaseService;
use Mockery;
use Tests\TestCase;

class PhaseServiceTest extends TestCase
{
    public function test_create_phase_delegates_to_repository(): void
    {
        $project = Mockery::mock(Project::class);
        $phase   = Mockery::mock(Phase::class);
        $data    = ['name' => 'Sprint 1', 'start_date' => '2026-06-01', 'due_date' => '2026-06-14'];

        $repo = Mockery::mock(PhaseRepository::class);
        $repo->shouldReceive('create')->with($project, $data)->once()->andReturn($phase);

        $service = new PhaseService($repo);
        $result  = $service->createPhase($project, $data);

        $this->assertSame($phase, $result);
    }

    public function test_update_phase_delegates_to_repository(): void
    {
        $phase   = Mockery::mock(Phase::class);
        $updated = Mockery::mock(Phase::class);
        $data    = ['name' => 'Sprint 2'];

        $repo = Mockery::mock(PhaseRepository::class);
        $repo->shouldReceive('update')->with($phase, $data)->once()->andReturn($updated);

        $service = new PhaseService($repo);
        $result  = $service->updatePhase($phase, $data);

        $this->assertSame($updated, $result);
    }

    public function test_delete_phase_delegates_to_repository_and_returns_bool(): void
    {
        $phase = Mockery::mock(Phase::class);

        $repo = Mockery::mock(PhaseRepository::class);
        $repo->shouldReceive('delete')->with($phase)->once()->andReturn(true);

        $service = new PhaseService($repo);
        $result  = $service->deletePhase($phase);

        $this->assertTrue($result);
    }
}
