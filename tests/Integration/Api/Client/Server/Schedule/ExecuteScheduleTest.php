<?php

namespace Kriegerhost\Tests\Integration\Api\Client\Server\Schedule;

use Kriegerhost\Models\Task;
use Illuminate\Http\Response;
use Kriegerhost\Models\Schedule;
use Kriegerhost\Models\Permission;
use Illuminate\Support\Facades\Bus;
use Kriegerhost\Jobs\Schedule\RunTaskJob;
use Kriegerhost\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class ExecuteScheduleTest extends ClientApiIntegrationTestCase
{
    /**
     * Test that a schedule can be executed and is updated in the database correctly.
     *
     * @dataProvider permissionsDataProvider
     */
    public function testScheduleIsExecutedRightAway(array $permissions)
    {
        [$user, $server] = $this->generateTestAccount($permissions);

        Bus::fake();

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create([
            'server_id' => $server->id,
        ]);

        $response = $this->actingAs($user)->postJson($this->link($schedule, '/execute'));
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJsonPath('errors.0.code', 'DisplayException');
        $response->assertJsonPath('errors.0.detail', 'Cannot process schedule for task execution: no tasks are registered.');

        /** @var \Kriegerhost\Models\Task $task */
        $task = Task::factory()->create([
            'schedule_id' => $schedule->id,
            'sequence_id' => 1,
            'time_offset' => 2,
        ]);

        $this->actingAs($user)->postJson($this->link($schedule, '/execute'))->assertStatus(Response::HTTP_ACCEPTED);

        Bus::assertDispatched(function (RunTaskJob $job) use ($task) {
            // A task executed right now should not have any job delay associated with it.
            $this->assertNull($job->delay);
            $this->assertSame($task->id, $job->task->id);

            return true;
        });
    }

    /**
     * Test that a user without the schedule update permission cannot execute it.
     */
    public function testUserWithoutScheduleUpdatePermissionCannotExecute()
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_SCHEDULE_CREATE]);

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);

        $this->actingAs($user)->postJson($this->link($schedule, '/execute'))->assertForbidden();
    }

    public function permissionsDataProvider(): array
    {
        return [[[]], [[Permission::ACTION_SCHEDULE_UPDATE]]];
    }
}
