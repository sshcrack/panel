<?php

namespace Kriegerhost\Http\Requests\Admin;

use Kriegerhost\Models\Mount;

class MountFormRequest extends AdminFormRequest
{
    /**
     * Setup the validation rules to use for these requests.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return Mount::getRulesForUpdate($this->route()->parameter('mount')->id);
        }

        return Mount::getRules();
    }
}
