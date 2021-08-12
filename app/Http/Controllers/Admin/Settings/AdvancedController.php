<?php

namespace Kriegerhost\Http\Controllers\Admin\Settings;

use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Illuminate\Contracts\Console\Kernel;
use Kriegerhost\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Kriegerhost\Contracts\Repository\SettingsRepositoryInterface;
use Kriegerhost\Http\Requests\Admin\Settings\AdvancedSettingsFormRequest;

class AdvancedController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Contracts\Console\Kernel
     */
    private $kernel;

    /**
     * @var \Kriegerhost\Contracts\Repository\SettingsRepositoryInterface
     */
    private $settings;

    /**
     * AdvancedController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        ConfigRepository $config,
        Kernel $kernel,
        SettingsRepositoryInterface $settings
    ) {
        $this->alert = $alert;
        $this->config = $config;
        $this->kernel = $kernel;
        $this->settings = $settings;
    }

    /**
     * Render advanced Panel settings UI.
     */
    public function index(): View
    {
        $showRecaptchaWarning = false;
        if (
            $this->config->get('recaptcha._shipped_secret_key') === $this->config->get('recaptcha.secret_key')
            || $this->config->get('recaptcha._shipped_website_key') === $this->config->get('recaptcha.website_key')
        ) {
            $showRecaptchaWarning = true;
        }

        return view('admin.settings.advanced', [
            'showRecaptchaWarning' => $showRecaptchaWarning,
        ]);
    }

    /**
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function update(AdvancedSettingsFormRequest $request): RedirectResponse
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::' . $key, $value);
        }

        $this->kernel->call('queue:restart');
        $this->alert->success('Advanced settings have been updated successfully and the queue worker was restarted to apply these changes.')->flash();

        return redirect()->route('admin.settings.advanced');
    }
}
