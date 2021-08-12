<?php

namespace Kriegerhost\Http\Requests\Api\Client\Servers\Schedules;

use Kriegerhost\Models\Permission;

class UpdateScheduleRequest extends StoreScheduleRequest
{
    public function permission(): string
    {
        return Permission::ACTION_SCHEDULE_UPDATE;
    }
}
