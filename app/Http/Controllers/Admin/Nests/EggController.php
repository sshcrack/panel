<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Controllers\Admin\Nests;

use Javascript;
use Illuminate\View\View;
use Kriegerhost\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Services\Eggs\EggUpdateService;
use Kriegerhost\Services\Eggs\EggCreationService;
use Kriegerhost\Services\Eggs\EggDeletionService;
use Kriegerhost\Http\Requests\Admin\Egg\EggFormRequest;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;

class EggController extends Controller
{
    protected $alert;

    protected $creationService;

    protected $deletionService;

    protected $nestRepository;

    protected $repository;

    protected $updateService;

    public function __construct(
        AlertsMessageBag $alert,
        EggCreationService $creationService,
        EggDeletionService $deletionService,
        EggRepositoryInterface $repository,
        EggUpdateService $updateService,
        NestRepositoryInterface $nestRepository
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->nestRepository = $nestRepository;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Handle a request to display the Egg creation page.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function create(): View
    {
        $nests = $this->nestRepository->getWithEggs();
        Javascript::put(['nests' => $nests->keyBy('id')]);

        return view('admin.eggs.new', ['nests' => $nests]);
    }

    /**
     * Handle request to store a new Egg.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function store(EggFormRequest $request): RedirectResponse
    {
        $data = $request->normalize();
        if (!empty($data['docker_images']) && !is_array($data['docker_images'])) {
            $data['docker_images'] = array_map(function ($value) {
                return trim($value);
            }, explode("\n", $data['docker_images']));
        }

        $egg = $this->creationService->handle($data);
        $this->alert->success(trans('admin/nests.eggs.notices.egg_created'))->flash();

        return redirect()->route('admin.nests.egg.view', $egg->id);
    }

    /**
     * Handle request to view a single Egg.
     */
    public function view(Egg $egg): View
    {
        return view('admin.eggs.view', ['egg' => $egg]);
    }

    /**
     * Handle request to update an Egg.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function update(EggFormRequest $request, Egg $egg): RedirectResponse
    {
        $data = $request->normalize();
        if (!empty($data['docker_images']) && !is_array($data['docker_images'])) {
            $data['docker_images'] = array_map(function ($value) {
                return trim($value);
            }, explode("\n", $data['docker_images']));
        }

        $this->updateService->handle($egg, $data);
        $this->alert->success(trans('admin/nests.eggs.notices.updated'))->flash();

        return redirect()->route('admin.nests.egg.view', $egg->id);
    }

    /**
     * Handle request to destroy an egg.
     *
     * @throws \Kriegerhost\Exceptions\Service\Egg\HasChildrenException
     * @throws \Kriegerhost\Exceptions\Service\HasActiveServersException
     */
    public function destroy(Egg $egg): RedirectResponse
    {
        $this->deletionService->handle($egg->id);
        $this->alert->success(trans('admin/nests.eggs.notices.deleted'))->flash();

        return redirect()->route('admin.nests.view', $egg->nest_id);
    }
}
