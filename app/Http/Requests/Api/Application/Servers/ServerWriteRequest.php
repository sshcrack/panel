<?php

namespace Kriegerhost\Http\Requests\Api\Application\Servers;

use Kriegerhost\Services\Acl\Api\AdminAcl;
use Kriegerhost\Http\Requests\Api\Application\ApplicationApiRequest;

class ServerWriteRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_SERVERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;
}
