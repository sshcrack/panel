<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Settings;

use Webmozart\Assert\Assert;
use Kriegerhost\Models\Server;
use Illuminate\Validation\Rule;
use Kriegerhost\Models\Permission;
use Kriegerhost\Contracts\Http\ClientPermissionsRequest;
use Kriegerhost\Http\Requests\Api\Client\ClientApiRequest;

class SetDockerImageRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_STARTUP_DOCKER_IMAGE;
    }

    /**
     * @return array[]
     */
    public function rules(): array
    {
        /** @var \Kriegerhost\Models\Server $server */
        $server = $this->route()->parameter('server');

        Assert::isInstanceOf($server, Server::class);

        return [
            'docker_image' => ['required', 'string', Rule::in($server->egg->docker_images)],
        ];
    }
}
