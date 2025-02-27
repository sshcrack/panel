<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Services\Servers\StartupCommandService;
use Kriegerhost\Services\Servers\VariableValidatorService;
use Kriegerhost\Repositories\Eloquent\ServerVariableRepository;
use Kriegerhost\Transformers\Api\Client\EggVariableTransformer;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Kriegerhost\Http\Requests\Api\Client\Servers\Startup\GetStartupRequest;
use Kriegerhost\Http\Requests\Api\Client\Servers\Startup\UpdateStartupVariableRequest;

class StartupController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Services\Servers\VariableValidatorService
     */
    private $service;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\ServerVariableRepository
     */
    private $repository;

    /**
     * @var \Kriegerhost\Services\Servers\StartupCommandService
     */
    private $startupCommandService;

    /**
     * StartupController constructor.
     */
    public function __construct(VariableValidatorService $service, StartupCommandService $startupCommandService, ServerVariableRepository $repository)
    {
        parent::__construct();

        $this->service = $service;
        $this->repository = $repository;
        $this->startupCommandService = $startupCommandService;
    }

    /**
     * Returns the startup information for the server including all of the variables.
     *
     * @return array
     */
    public function index(GetStartupRequest $request, Server $server)
    {
        $startup = $this->startupCommandService->handle($server, false);

        return $this->fractal->collection(
            $server->variables()->where('user_viewable', true)->get()
        )
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->addMeta([
                'startup_command' => $startup,
                'docker_images' => $server->egg->docker_images,
                'raw_startup_command' => $server->startup,
            ])
            ->toArray();
    }

    /**
     * Updates a single variable for a server.
     *
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(UpdateStartupVariableRequest $request, Server $server)
    {
        /** @var \Kriegerhost\Models\EggVariable $variable */
        $variable = $server->variables()->where('env_variable', $request->input('key'))->first();

        if (is_null($variable) || !$variable->user_viewable) {
            throw new BadRequestHttpException('The environment variable you are trying to edit does not exist.');
        } elseif (!$variable->user_editable) {
            throw new BadRequestHttpException('The environment variable you are trying to edit is read-only.');
        }

        // Revalidate the variable value using the egg variable specific validation rules for it.
        $this->validate($request, ['value' => $variable->rules]);

        $this->repository->updateOrCreate([
            'server_id' => $server->id,
            'variable_id' => $variable->id,
        ], [
            'variable_value' => $request->input('value') ?? '',
        ]);

        $variable = $variable->refresh();
        $variable->server_value = $request->input('value');

        $startup = $this->startupCommandService->handle($server, false);

        return $this->fractal->item($variable)
            ->transformWith($this->getTransformer(EggVariableTransformer::class))
            ->addMeta([
                'startup_command' => $startup,
                'raw_startup_command' => $server->startup,
            ])
            ->toArray();
    }
}
