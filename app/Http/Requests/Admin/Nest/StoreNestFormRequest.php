<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Http\Requests\Admin\Nest;

use Kriegerhost\Http\Requests\Admin\AdminFormRequest;

class StoreNestFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:191',
            'description' => 'string|nullable',
        ];
    }
}
