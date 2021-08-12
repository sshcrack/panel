<?php

namespace Kriegerhost\Http\Controllers\Api\Client\Servers;

use Carbon\CarbonImmutable;
use Kriegerhost\Models\User;
use Kriegerhost\Models\Server;
use Illuminate\Http\JsonResponse;
use Kriegerhost\Services\Nodes\NodeJWTService;
use Kriegerhost\Http\Controllers\Api\Client\ClientApiController;
use Kriegerhost\Http\Requests\Api\Client\Servers\Files\UploadFileRequest;

class FileUploadController extends ClientApiController
{
    /**
     * @var \Kriegerhost\Services\Nodes\NodeJWTService
     */
    private $jwtService;

    /**
     * FileUploadController constructor.
     */
    public function __construct(
        NodeJWTService $jwtService
    ) {
        parent::__construct();

        $this->jwtService = $jwtService;
    }

    /**
     * Returns a url where files can be uploaded to.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UploadFileRequest $request, Server $server)
    {
        return new JsonResponse([
            'object' => 'signed_url',
            'attributes' => [
                'url' => $this->getUploadUrl($server, $request->user()),
            ],
        ]);
    }

    /**
     * Returns a url where files can be uploaded to.
     *
     * @return string
     */
    protected function getUploadUrl(Server $server, User $user)
    {
        $token = $this->jwtService
            ->setExpiresAt(CarbonImmutable::now()->addMinutes(15))
            ->setClaims([
                'server_uuid' => $server->uuid,
            ])
            ->handle($server->node, $user->id . $server->uuid);

        return sprintf(
            '%s/upload/file?token=%s',
            $server->node->getConnectionAddress(),
            $token->toString()
        );
    }
}
