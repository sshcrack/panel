<?php

namespace Kriegerhost\Http\Requests\Api\Application\Nests\Eggs;

use Kriegerhost\Models\Egg;
use Kriegerhost\Models\Nest;
use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEggRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_EGGS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested egg exists for the selected nest.
     */
    public function resourceExists(): bool
    {
        return $this->getModel(Nest::class)->id === $this->getModel(Egg::class)->nest_id;
    }
}
