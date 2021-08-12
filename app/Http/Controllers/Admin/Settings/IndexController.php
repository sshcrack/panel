<?php

namespace Kriegerhost\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Traits\Helpers\AvailableLanguages;
use Kriegerhost\Services\Helpers\SoftwareVersionService;
use Kriegerhost\Contracts\Repository\SettingsRepositoryInterface;
use Kriegerhost\Http\Requests\Admin\Settings\BaseSettingsFormRequest;

class IndexController extends Controller
{
    use AvailableLanguages;

    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Kriegerhost\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * @var \Kriegerhost\Services\Helpers\SoftwareVersionService
     */
    private $versionService;

    /**
     * IndexController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        Kernel $kernel,
        SettingsRepositoryInterface $settings,
        SoftwareVersionService $versionService
    ) {
        $this->alert = $alert;
        $this->kernel = $kernel;
        $this->settings = $settings;
        $this->versionService = $versionService;
    }

    /**
     * Render the UI for basic Panel settings.
     */
    public function index(): View
    {
        return view('admin.settings.index', [
            'version' => $this->versionService,
            'languages' => $this->getAvailableLanguages(true),
        ]);
    }

    /**
     * Handle settings update.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(BaseSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Panel settings have been updated successfully and the queue worker was restarted to apply these changes.')->flash();

        return redirect()->route('admin.settings');
    }
}
