<?php

namespace Kriegerhost\Http\Requests\Api\Application\Servers;

use Kriegerhost\Models\Server;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class UpdateServerStartupRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Validation rules to run the input against.
     */
    public function rules(): array
    {
        $data = Server::getRulesForUpdate($this->getModel(Server::class));

        return [
            'startup' => $data['startup'],
            'environment' => 'present|array',
            'egg' => $data['egg_id'],
            'image' => $data['image'],
            'skip_scripts' => 'present|boolean',
        ];
    }

    /**
     * Return the validated data in a format that is expected by the service.
     *
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        return collect($data)->only(['startup', 'environment', 'skip_scripts'])->merge([
            'egg_id' => array_get($data, 'egg'),
            'docker_image' => array_get($data, 'image'),
        ])->toArray();
    }
}
