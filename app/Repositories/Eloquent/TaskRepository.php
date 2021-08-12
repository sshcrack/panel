<?php

namespace Kriegerhost\Repositories\Eloquent;

use Kriegerhost\Models\Task;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Kriegerhost\Contracts\Repository\TaskRepositoryInterface;
use Kriegerhost\Exceptions\Repository\RecordNotFoundException;

class TaskRepository extends EloquentRepository implements TaskRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Task::class;
    }

    /**
     * Get a task and the server relationship for that task.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function getTaskForJobProcess(int $id): Task
    {
        try {
            return $this->getBuilder()->with('server.user', 'schedule')->findOrFail($id, $this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException();
        }
    }

    /**
     * Returns the next task in a schedule.
     *
     * @return \Kriegerhost\Models\Task|null
     */
    public function getNextTask(int $schedule, int $index)
    {
        return $this->getBuilder()->where('schedule_id', '=', $schedule)
            ->where('sequence_id', '=', $index + 1)
            ->first($this->getColumns());
    }
}
