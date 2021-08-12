<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Controllers\Admin;

use Kriegerhost\Models\Location;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Exceptions\DisplayException;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Http\Requests\Admin\LocationFormRequest;
use Kriegerhost\Services\Locations\LocationUpdateService;
use Kriegerhost\Services\Locations\LocationCreationService;
use Kriegerhost\Services\Locations\LocationDeletionService;
use Kriegerhost\Contracts\Repository\LocationRepositoryInterface;

class LocationController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Services\Locations\LocationCreationService
     */
    protected $creationService;

    /**
     * @var \Kriegerhost\Services\Locations\LocationDeletionService
     */
    protected $deletionService;

    /**
     * @var \Kriegerhost\Contracts\Repository\LocationRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Kriegerhost\Services\Locations\LocationUpdateService
     */
    protected $updateService;

    /**
     * LocationController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        LocationCreationService $creationService,
        LocationDeletionService $deletionService,
        LocationRepositoryInterface $repository,
        LocationUpdateService $updateService
    ) {
        $this->alert = $alert;
        $this->creationService = $creationService;
        $this->deletionService = $deletionService;
        $this->repository = $repository;
        $this->updateService = $updateService;
    }

    /**
     * Return the location overview page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.locations.index', [
            'locations' => $this->repository->getAllWithDetails(),
        ]);
    }

    /**
     * Return the location view page.
     *
     * @param int $id
     *
     * @return \Illuminate\View\View
     *
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function view($id)
    {
        return view('admin.locations.view', [
            'location' => $this->repository->getWithNodes($id),
        ]);
    }

    /**
     * Handle request to create new location.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function create(LocationFormRequest $request)
    {
        $location = $this->creationService->handle($request->normalize());
        $this->alert->success('Location was created successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Handle request to update or delete location.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Throwable
     */
    public function update(LocationFormRequest $request, Location $location)
    {
        if ($request->input('action') === 'delete') {
            return $this->delete($location);
        }

        $this->updateService->handle($location->id, $request->normalize());
        $this->alert->success('Location was updated successfully.')->flash();

        return redirect()->route('admin.locations.view', $location->id);
    }

    /**
     * Delete a location from the system.
     *
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Exception
     * @throws \Kriegerhost\Exceptions\DisplayException
     */
    public function delete(Location $location)
    {
        try {
            $this->deletionService->handle($location->id);

            return redirect()->route('admin.locations');
        } catch (DisplayException $ex) {
            $this->alert->danger($ex->getMessage())->flash();
        }

        return redirect()->route('admin.locations.view', $location->id);
    }
}
