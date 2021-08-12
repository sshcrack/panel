<?php

namespace Kriegerhost\Contracts\Repository;

use Kriegerhost\Models\Task;

interface TaskRepositoryInterface extends RepositoryInterface
{
    /**
     * Get a task and the server relationship for that task.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getTaskForJobProcess(int $id): Task;

    /**
     * Returns the next task in a schedule.
     *
     * @return \Kriegerhost\Models\Task|null
     */
    public function getNextTask(int $schedule, int $index);
}
