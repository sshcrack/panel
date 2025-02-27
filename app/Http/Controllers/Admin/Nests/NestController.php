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
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Services\Nests\NestUpdateService;
use Kriegerhost\Services\Nests\NestCreationService;
use Kriegerhost\Services\Nests\NestDeletionService;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Kriegerhost\Http\Requests\Admin\Nest\StoreNestFormRequest;

class NestController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Services\Nests\NestCreationService
     */
    protected $nestCreationService;

    /**
     * @var \Kriegerhost\Services\Nests\NestDeletionService
     */
    protected $nestDeletionService;

    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Kriegerhost\Services\Nests\NestUpdateService
     */
    protected $nestUpdateService;

    /**
     * NestController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        NestCreationService $nestCreationService,
        NestDeletionService $nestDeletionService,
        NestRepositoryInterface $repository,
        NestUpdateService $nestUpdateService
    ) {
        $this->alert = $alert;
        $this->nestDeletionService = $nestDeletionService;
        $this->nestCreationService = $nestCreationService;
        $this->nestUpdateService = $nestUpdateService;
        $this->repository = $repository;
    }

    /**
     * Render nest listing page.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function index(): View
    {
        return view('admin.nests.index', [
            'nests' => $this->repository->getWithCounts(),
        ]);
    }

    /**
     * Render nest creation page.
     */
    public function create(): View
    {
        return view('admin.nests.new');
    }

    /**
     * Handle the storage of a new nest.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreNestFormRequest $request): RedirectResponse
    {
        $nest = $this->nestCreationService->handle($request->normalize());
        $this->alert->success(trans('admin/nests.notices.created', ['name' => $nest->name]))->flash();

        return redirect()->route('admin.nests.view', $nest->id);
    }

    /**
     * Return details about a nest including all of the eggs and servers per egg.
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function view(int $nest): View
    {
        return view('admin.nests.view', [
            'nest' => $this->repository->getWithEggServers($nest),
        ]);
    }

    /**
     * Handle request to update a nest.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(StoreNestFormRequest $request, int $nest): RedirectResponse
    {
        $this->nestUpdateService->handle($nest, $request->normalize());
        $this->alert->success(trans('admin/nests.notices.updated'))->flash();

        return redirect()->route('admin.nests.view', $nest);
    }

    /**
     * Handle request to delete a nest.
     *
     * @throws \Kriegerhost\Exceptions\Service\HasActiveServersException
     */
    public function destroy(int $nest): RedirectResponse
    {
        $this->nestDeletionService->handle($nest);
        $this->alert->success(trans('admin/nests.notices.deleted'))->flash();

        return redirect()->route('admin.nests');
    }
}
