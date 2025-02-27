<?php

namespace Kriegerhost\Console\Commands\Schedule;

use Exception;
use Throwable;
use Illuminate\Console\Command;
use Kriegerhost\Models\Schedule;
use Illuminate\Support\Facades\Log;
use Kriegerhost\Services\Schedules\ProcessScheduleService;

class ProcessRunnableCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'p:schedule:process';

    /**
     * @var string
     */
    protected $description = 'Process schedules in the database and determine which are ready to run.';

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $schedules = Schedule::query()->with('tasks')
            ->where('is_active', true)
            ->where('is_processing', false)
            ->whereRaw('next_run_at <= NOW()')
            ->get();

        if ($schedules->count() < 1) {
            $this->line('There are no scheduled tasks for servers that need to be run.');

            return;
        }

        $bar = $this->output->createProgressBar(count($schedules));
        foreach ($schedules as $schedule) {
            $bar->clear();
            $this->processSchedule($schedule);
            $bar->advance();
            $bar->display();
        }

        $this->line('');
    }

    /**
     * Processes a given schedule and logs and errors encountered the console output. This should
     * never throw an exception out, otherwise you'll end up killing the entire run group causing
     * any other schedules to not process correctly.
     *
     * @see https://github.com/kriegerhost/panel/issues/2609
     */
    protected function processSchedule(Schedule $schedule)
    {
        if ($schedule->tasks->isEmpty()) {
            return;
        }

        try {
            $this->getLaravel()->make(ProcessScheduleService::class)->handle($schedule);

            $this->line(trans('command/messages.schedule.output_line', [
                'schedule' => $schedule->name,
                'hash' => $schedule->hashid,
            ]));
        } catch (Throwable | Exception $exception) {
            Log::error($exception, ['schedule_id' => $schedule->id]);

            $this->error("An error was encountered while processing Schedule #{$schedule->id}: " . $exception->getMessage());
        }
    }
}
