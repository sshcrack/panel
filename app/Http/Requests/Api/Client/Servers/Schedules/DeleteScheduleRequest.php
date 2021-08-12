<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Schedules;

use Kriegerhost\Models\Permission;

class DeleteScheduleRequest extends ViewScheduleRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_DELETE;
    }
}
