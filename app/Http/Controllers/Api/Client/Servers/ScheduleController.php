<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Helpers\Utilities;
use Kriegerhost\Exceptions\DisplayException;
use Kriegerhost\Repositories\Eloquent\ScheduleRepository;
use Kriegerhost\Services\Schedules\ProcessScheduleService;
use Kriegerhost\Transformers\Api\Client\ScheduleTransformer;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kriegerhost\Http\Requests\Api\Client\Servers\Schedules\ViewScheduleRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Schedules\StoreScheduleRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Schedules\DeleteScheduleRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Schedules\UpdateScheduleRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Schedules\TriggerScheduleRequest;

class ScheduleController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Repositories\Eloquent\ScheduleRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Schedules\ProcessScheduleService
     */
    private $service;

    /**
     * ScheduleController constructor.
     */
    public function __construct(ScheduleRepository $repository, ProcessScheduleService $service)
    {
        parent::__construct();

        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Returns all of the schedules belonging to a given server.
     *
     * @return array
     */
    public function index(ViewScheduleRequest $request, Server $server)
    {
        $schedules = $server->schedule;
        $schedules->loadMissing('tasks');

        return $this->fractal->collection($schedules)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Store a new schedule for a server.
     *
     * @return array
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreScheduleRequest $request, Server $server)
    {
        /** @var \Kriegerhost\Models\Schedule $model */
        $model = $this->repository->create([
            'server_id' => $server->id,
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_month' => $request->input('month'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => (bool) $request->input('is_active'),
            'only_when_online' => (bool) $request->input('only_when_online'),
            'next_run_at' => $this->getNextRunAt($request),
        ]);

        return $this->fractal->item($model)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Returns a specific schedule for the server.
     *
     * @return array
     */
    public function view(ViewScheduleRequest $request, Server $server, Schedule $schedule)
    {
        if ($schedule->server_id !== $server->id) {
            throw new NotFoundHttpException();
        }

        $schedule->loadMissing('tasks');

        return $this->fractal->item($schedule)
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Updates a given schedule with the new data provided.
     *
     * @return array
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateScheduleRequest $request, Server $server, Schedule $schedule)
    {
        $active = (bool) $request->input('is_active');

        $data = [
            'name' => $request->input('name'),
            'cron_day_of_week' => $request->input('day_of_week'),
            'cron_month' => $request->input('month'),
            'cron_day_of_month' => $request->input('day_of_month'),
            'cron_hour' => $request->input('hour'),
            'cron_minute' => $request->input('minute'),
            'is_active' => $active,
            'only_when_online' => (bool) $request->input('only_when_online'),
            'next_run_at' => $this->getNextRunAt($request),
        ];

        // Toggle the processing state of the scheduled task when it is enabled or disabled so that an
        // invalid state can be reset without manual database intervention.
        //
        // @see https://github.com/kriegerhost/panel/issues/2425
        if ($schedule->is_active !== $active) {
            $data['is_processing'] = false;
        }

        $this->repository->update($schedule->id, $data);

        return $this->fractal->item($schedule->refresh())
            ->transformWith($this->getTransformer(ScheduleTransformer::class))
            ->toArray();
    }

    /**
     * Executes a given schedule immediately rather than waiting on it's normally scheduled time
     * to pass. This does not care about the schedule state.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function execute(TriggerScheduleRequest $request, Server $server, Schedule $schedule)
    {
        $this->service->handle($schedule, true);

        return new JsonResponse([], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * Deletes a schedule and it's associated tasks.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteScheduleRequest $request, Server $server, Schedule $schedule)
    {
        $this->repository->delete($schedule->id);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the next run timestamp based on the cron data provided.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     */
    protected function getNextRunAt(Request $request): Carbon
    {
        try {
            return Utilities::getScheduleNextRunDate(
                $request->input('minute'),
                $request->input('hour'),
                $request->input('day_of_month'),
                $request->input('month'),
                $request->input('day_of_week')
            );
        } catch (Exception $exception) {
            throw new DisplayException('The cron data provided does not evaluate to a valid expression.');
        }
    }
}
