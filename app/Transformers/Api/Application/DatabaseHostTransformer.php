<?php

namespace Kriegerhost\Transformers\Api\Application;

use Kriegerhost\Models\Database;
use Kriegerhost\Models\DatabaseHost;
use Kriegerhost\Services\Acl\Api\AdminAcl;

class DatabaseHostTransformer extends BaseTransformer
{
    /**
     * @var array
     */
    protected $availableIncludes = [
        'databases',
    ];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return DatabaseHost::RESOURCE_NAME;
    }

    /**
     * Transform database host into a representation for the application API.
     *
     * @return array
     */
    public function transform(DatabaseHost $model)
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'host' => $model->host,
            'port' => $model->port,
            'username' => $model->username,
            'node' => $model->node_id,
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String(),
        ];
    }

    /**
     * Include the databases associated with this host.
     *
     * @return \League\Fractal\Resource\Collection|\League\Fractal\Resource\NullResource
     *
     * @throws \Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeDatabases(DatabaseHost $model)
    {
        if (!$this->authorize(AdminAcl::RESOURCE_SERVER_DATABASES)) {
            return $this->null();
        }

        $model->loadMissing('databases');

        return $this->collection($model->getRelation('databases'), $this->makeTransformer(ServerDatabaseTransformer::class), Database::RESOURCE_NAME);
    }
}
