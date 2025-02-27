<?php

namespace Kriegerhost\Http\Controllers\Api\Client;

use Kriegerhost\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Exceptions\DisplayException;
use Illuminate\Contracts\Encryption\Encrypter;
use Kriegerhost\Services\Api\KeyCreationService;
use Kriegerhost\Repositories\Eloquent\ApiKeyRepository;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;
use Kriegerhost\Transformers\Api\Client\ApiKeyTransformer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Kriegerhost\Http\Requests\Api\Client\Account\StoreApiKeyRequest;

class ApiKeyController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Services\Api\KeyCreationService
     */
    private $keyCreationService;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * @var \Kriegerhost\Repositories\Eloquent\ApiKeyRepository
     */
    private $repository;

    /**
     * ApiKeyController constructor.
     */
    public function __construct(
        Encrypter $encrypter,
        KeyCreationService $keyCreationService,
        ApiKeyRepository $repository
    ) {
        parent::__construct();

        $this->encrypter = $encrypter;
        $this->keyCreationService = $keyCreationService;
        $this->repository = $repository;
    }

    /**
     * Returns all of the API keys that exist for the given client.
     *
     * @return array
     */
    public function index(ClientApiRequest $request)
    {
        return $this->fractal->collection($request->user()->apiKeys)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->toArray();
    }

    /**
     * Store a new API key for a user's account.
     *
     * @return array
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function store(StoreApiKeyRequest $request)
    {
        if ($request->user()->apiKeys->count() >= 5) {
            throw new DisplayException('You have reached the account limit for number of API keys.');
        }

        $key = $this->keyCreationService->setKeyType(ApiKey::TYPE_ACCOUNT)->handle([
            'user_id' => $request->user()->id,
            'memo' => $request->input('description'),
            'allowed_ips' => $request->input('allowed_ips') ?? [],
        ]);

        return $this->fractal->item($key)
            ->transformWith($this->getTransformer(ApiKeyTransformer::class))
            ->addMeta([
                'secret_token' => $this->encrypter->decrypt($key->token),
            ])
            ->toArray();
    }

    /**
     * Deletes a given API key.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ClientApiRequest $request, string $identifier)
    {
        $response = $this->repository->deleteWhere([
            'key_type' => ApiKey::TYPE_ACCOUNT,
            'user_id' => $request->user()->id,
            'identifier' => $identifier,
        ]);

        if (!$response) {
            throw new NotFoundHttpException();
        }

        return JsonResponse::create([], JsonResponse::HTTP_NO_CONTENT);
    }
}
