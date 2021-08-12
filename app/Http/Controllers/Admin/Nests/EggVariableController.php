<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Controllers\Admin\Nests;

use Illuminate\View\View;
use Kriegerhost\Models\Egg;
use Kriegerhost\Models\EggVariable;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Services\Eggs\Variables\VariableUpdateService;
use Kriegerhost\Http\Requests\Admin\Egg\EggVariableFormRequest;
use Kriegerhost\Services\Eggs\Variables\VariableCreationService;
use Kriegerhost\Contracts\Repository\EggVariableRepositoryInterface;

class EggVariableController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Services\Eggs\Variables\VariableCreationService
     */
    protected $creationService;

    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Kriegerhost\Services\Eggs\Variables\VariableUpdateService
     */
    protected $updateService;

    /**
     * @var \Kriegerhost\Contracts\Repository\EggVariableRepositoryInterface
     */
    protected $variableRepository;

    /**
     * EggVariableController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        VariableCreationService $creationService,
        VariableUpdateService $updateService,
        EggRepositoryInterface $repository,
        EggVariableRepositoryInterface $variableRepository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->repository = $repository;
        $this->updateService = $updateService;
        $this->variableRepository = $variableRepository;
    }

    /**
     * Handle request to view the variables attached to an Egg.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function view(int $egg): View
    {
        $egg = $this->repository->getWithVariables($egg);

        return view('admin.eggs.variables', ['egg' => $egg]);
    }

    /**
     * Handle a request to create a new Egg variable.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \Kriegerhost\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function store(EggVariableFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->creationService->handle($egg->id, $request->normalize());
        $this->alert->success(trans('admin/nests.variables.notices.variable_created'))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg->id);
    }

    /**
     * Handle a request to update an existing Egg variable.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function update(EggVariableFormRequest $request, Egg $egg, EggVariable $variable): RedirectResponse
    {
        $this->updateService->handle($variable, $request->normalize());
        $this->alert->success(trans('admin/nests.variables.notices.variable_updated', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg->id);
    }

    /**
     * Handle a request to delete an existing Egg variable from the Panel.
     */
    public function destroy(int $egg, EggVariable $variable): RedirectResponse
    {
        $this->variableRepository->delete($variable->id);
        $this->alert->success(trans('admin/nests.variables.notices.variable_deleted', [
            'variable' => $variable->name,
        ]))->flash();

        return redirect()->route('admin.nests.egg.variables', $egg);
    }
}
