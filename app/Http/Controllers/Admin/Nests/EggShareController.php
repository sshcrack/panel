<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Controllers\Admin\Nests;

use Kriegerhost\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;
use Kriegerhost\Services\Eggs\Sharing\EggExporterService;
use Kriegerhost\Services\Eggs\Sharing\EggImporterService;
use Kriegerhost\Http\Requests\Admin\Egg\EggImportFormRequest;
use Kriegerhost\Services\Eggs\Sharing\EggUpdateImporterService;

class EggShareController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Services\Eggs\Sharing\EggExporterService
     */
    protected $exporterService;

    /**
     * @var \Kriegerhost\Services\Eggs\Sharing\EggImporterService
     */
    protected $importerService;

    /**
     * @var \Kriegerhost\Services\Eggs\Sharing\EggUpdateImporterService
     */
    protected $updateImporterService;

    /**
     * OptionShareController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        EggExporterService $exporterService,
        EggImporterService $importerService,
        EggUpdateImporterService $updateImporterService
    ) {
        $this->alert = $alert;
        $this->exporterService = $exporterService;
        $this->importerService = $importerService;
        $this->updateImporterService = $updateImporterService;
    }

    /**
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function export(Egg $egg): Response
    {
        $filename = trim(preg_replace('/[^\w]/', '-', kebab_case($egg->name)), '-');

        return response($this->exporterService->handle($egg->id), 200, [
            'Content-Transfer-Encoding' => 'binary',
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => 'attachment; filename=egg-' . $filename . '.json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a new service option using an XML file.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Kriegerhost\Exceptions\Service\InvalidFileUploadException
     */
    public function import(EggImportFormRequest $request): RedirectResponse
    {
        $egg = $this->importerService->handle($request->file('import_file'), $request->input('import_to_nest'));
        $this->alert->success(trans('admin/nests.eggs.notices.imported'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg->id]);
    }

    /**
     * Update an existing Egg using a new imported file.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\BadJsonFormatException
     * @throws \Kriegerhost\Exceptions\Service\InvalidFileUploadException
     */
    public function update(EggImportFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->updateImporterService->handle($egg, $request->file('import_file'));
        $this->alert->success(trans('admin/nests.eggs.notices.updated_via_import'))->flash();

        return redirect()->route('admin.nests.egg.view', ['egg' => $egg]);
    }
}
