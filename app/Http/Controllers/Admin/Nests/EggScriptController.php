<?php

namespace Kriegerhost\Http\Controllers\Admin\Nests;

use Illuminate\View\View;
use Kriegerhost\Models\Egg;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Services\Eggs\Scripts\InstallScriptService;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Http\Requests\Admin\Egg\EggScriptFormRequest;

class EggScriptController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    protected $alert;

    /**
     * @var \Kriegerhost\Services\Eggs\Scripts\InstallScriptService
     */
    protected $installScriptService;

    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggScriptController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        EggRepositoryInterface $repository,
        InstallScriptService $installScriptService
    ) {
        $this->alert = $alert;
        $this->installScriptService = $installScriptService;
        $this->repository = $repository;
    }

    /**
     * Handle requests to render installation script for an Egg.
     */
    public function index(int $egg): View
    {
        $egg = $this->repository->getWithCopyAttributes($egg);
        $copy = $this->repository->findWhere([
            ['copy_script_from', '=', null],
            ['nest_id', '=', $egg->nest_id],
            ['id', '!=', $egg],
        ]);

        $rely = $this->repository->findWhere([
            ['copy_script_from', '=', $egg->id],
        ]);

        return view('admin.eggs.scripts', [
            'copyFromOptions' => $copy,
            'relyOnScript' => $rely,
            'egg' => $egg,
        ]);
    }

    /**
     * Handle a request to update the installation script for an Egg.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\InvalidCopyFromException
     */
    public function update(EggScriptFormRequest $request, Egg $egg): RedirectResponse
    {
        $this->installScriptService->handle($egg, $request->normalize());
        $this->alert->success(trans('admin/nests.eggs.notices.script_updated'))->flash();

        return redirect()->route('admin.nests.egg.scripts', $egg);
    }
}
