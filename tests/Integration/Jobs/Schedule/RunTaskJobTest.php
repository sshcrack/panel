<?php

namespace Kriegerhost\Tests\Integration\Jobs\Schedule;

use Mockery;
use Exception;
use Carbon\CarbonImmutable;
use Kriegerhost\Models\Task;
use GuzzleHttp\Psr7\Request;
use InvalidArgumentException;
use GuzzleHttp\Psr7\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Schedule;
use Illuminate\Support\Facades\Bus;
use Kriegerhost\Jobs\Schedule\RunTaskJob;
use GuzzleHttp\Exception\BadResponseException;
use Kriegerhost\Tests\Integration\IntegrationTestCase;
use Kriegerhost\Repositories\Wings\DaemonPowerRepository;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;

class RunTaskJobTest extends IntegrationTestCase
{
    /**
     * An inactive job should not be run by the system.
     */
    public function testInactiveJobIsNotRun()
    {
        $server = $this->createServerModel();

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create([
            'server_id' => $server->id,
            'is_processing' => true,
            'last_run_at' => null,
            'is_active' => false,
        ]);
        /** @var \Kriegerhost\Models\Task $task */
        $task = Task::factory()->create(['schedule_id' => $schedule->id, 'is_queued' => true]);

        $job = new RunTaskJob($task);

        Bus::dispatchNow($job);

        $task->refresh();
        $schedule->refresh();

        $this->assertFalse($task->is_queued);
        $this->assertFalse($schedule->is_processing);
        $this->assertFalse($schedule->is_active);
        $this->assertTrue(CarbonImmutable::now()->isSameAs(CarbonImmutable::ISO8601, $schedule->last_run_at));
    }

    public function testJobWithInvalidActionThrowsException()
    {
        $server = $this->createServerModel();

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        /** @var \Kriegerhost\Models\Task $task */
        $task = Task::factory()->create(['schedule_id' => $schedule->id, 'action' => 'foobar']);

        $job = new RunTaskJob($task);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid task action provided: foobar');
        Bus::dispatchNow($job);
    }

    /**
     * @dataProvider isManualRunDataProvider
     */
    public function testJobIsExecuted(bool $isManualRun)
    {
        $server = $this->createServerModel();

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create([
            'server_id' => $server->id,
            'is_active' => !$isManualRun,
            'is_processing' => true,
            'last_run_at' => null,
        ]);
        /** @var \Kriegerhost\Models\Task $task */
        $task = Task::factory()->create([
            'schedule_id' => $schedule->id,
            'action' => Task::ACTION_POWER,
            'payload' => 'start',
            'is_queued' => true,
            'continue_on_failure' => false,
        ]);

        $mock = Mockery::mock(DaemonPowerRepository::class);
        $this->instance(DaemonPowerRepository::class, $mock);

        $mock->expects('setServer')->with(Mockery::on(function ($value) use ($server) {
            return $value instanceof Server && $value->id === $server->id;
        }))->andReturnSelf();
        $mock->expects('send')->with('start')->andReturn(new Response());

        Bus::dispatchNow(new RunTaskJob($task, $isManualRun));

        $task->refresh();
        $schedule->refresh();

        $this->assertFalse($task->is_queued);
        $this->assertFalse($schedule->is_processing);
        $this->assertTrue(CarbonImmutable::now()->isSameAs(CarbonImmutable::ISO8601, $schedule->last_run_at));
    }

    /**
     * @dataProvider isManualRunDataProvider
     */
    public function testExceptionDuringRunIsHandledCorrectly(bool $continueOnFailure)
    {
        $server = $this->createServerModel();

        /** @var \Kriegerhost\Models\Schedule $schedule */
        $schedule = Schedule::factory()->create(['server_id' => $server->id]);
        /** @var \Kriegerhost\Models\Task $task */
        $task = Task::factory()->create([
            'schedule_id' => $schedule->id,
            'action' => Task::ACTION_POWER,
            'payload' => 'start',
            'continue_on_failure' => $continueOnFailure,
        ]);

        $mock = Mockery::mock(DaemonPowerRepository::class);
        $this->instance(DaemonPowerRepository::class, $mock);

        $mock->expects('setServer->send')->andThrow(
            new DaemonConnectionException(new BadResponseException('Bad request', new Request('GET', '/test'), new Response()))
        );

        if (!$continueOnFailure) {
            $this->expectException(DaemonConnectionException::class);
        }

        Bus::dispatchNow(new RunTaskJob($task));

        if ($continueOnFailure) {
            $task->refresh();
            $schedule->refresh();

            $this->assertFalse($task->is_queued);
            $this->assertFalse($schedule->is_processing);
            $this->assertTrue(CarbonImmutable::now()->isSameAs(CarbonImmutable::ISO8601, $schedule->last_run_at));
        }
    }

    /**
     * @return array
     */
    public function isManualRunDataProvider()
    {
        return [[true], [false]];
    }
}
