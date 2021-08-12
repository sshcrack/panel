<?php

namespace Kriegerhost\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Kriegerhost\Models\ApiKey;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Controllers\Controller;
use Kriegerhost\Services\Api\KeyCreationService;
use Kriegerhost\Contracts\Repository\ApiKeyRepositoryInterface;
use Kriegerhost\Http\Requests\Admin\Api\StoreApplicationApiKeyRequest;

class ApiController extends Controller
{
    /**
     * @var \Prologue\Alerts\AlertsMessageBag
     */
    private $alert;

    /**
     * @var \Kriegerhost\Services\Api\KeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \Kriegerhost\Contracts\Repository\ApiKeyRepositoryInterface
     */
    private $repository;

    /**
     * ApplicationApiController constructor.
     */
    public function __construct(
        AlertsMessageBag $alert,
        ApiKeyRepositoryInterface $repository,
        KeyCreationService $keyCreationService
    ) {
        $this->alert = $alert;
        $this->keyCreationService = $keyCreationService;
        $this->repository = $repository;
    }

    /**
     * Render view showing all of a user's application API keys.
     */
    public function index(Request $request): View
    {
        return view('admin.api.index', [
            'keys' => $this->repository->getApplicationKeys($request->user()),
        ]);
    }

    /**
     * Render view allowing an admin to create a new application API key.
     *
     * @throws \ReflectionException
     */
    public function create(): View
    {
        $resources = AdminAcl::getResourceList();
        sort($resources);

        return view('admin.api.new', [
            'resources' => $resources,
            'permissions' => [
                'r' => AdminAcl::READ,
                'rw' => AdminAcl::READ | AdminAcl::WRITE,
                'n' => AdminAcl::NONE,
            ],
        ]);
    }

    /**
     * Store the new key and redirect the user back to the application key listing.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreApplicationApiKeyRequest $request): RedirectResponse
    {
        $this->keyCreationService->setKeyType(ApiKey::TYPE_APPLICATION)->handle([
            'memo' => $request->input('memo'),
            'user_id' => $request->user()->id,
        ], $request->getKeyPermissions());

        $this->alert->success('A new application API key has been generated for your account.')->flash();

        return redirect()->route('admin.api.index');
    }

    /**
     * Delete an application API key from the database.
     */
    public function delete(Request $request, string $identifier): Response
    {
        $this->repository->deleteApplicationKey($request->user(), $identifier);

        return response('', 204);
    }
}
