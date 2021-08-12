<?php

namespace Kriegerhost\Http\Requests\Admin\Settings;

use Kriegerhost\Http\Requests\Admin\AdminFormRequest;

class AdvancedSettingsFormRequest extends AdminFormRequest
{
    /**
     * Return all of the rules to apply to this request's data.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'recaptcha:enabled' => 'required|in:true,false',
            'recaptcha:secret_key' => 'required|string|max:191',
            'recaptcha:website_key' => 'required|string|max:191',
            'kriegerhost:guzzle:timeout' => 'required|integer|between:1,60',
            'kriegerhost:guzzle:connect_timeout' => 'required|integer|between:1,60',
            'kriegerhost:client_features:allocations:enabled' => 'required|in:true,false',
            'kriegerhost:client_features:allocations:range_start' => [
                'nullable',
                'required_if:kriegerhost:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
            ],
            'kriegerhost:client_features:allocations:range_end' => [
                'nullable',
                'required_if:kriegerhost:client_features:allocations:enabled,true',
                'integer',
                'between:1024,65535',
                'gt:kriegerhost:client_features:allocations:range_start',
            ],
        ];
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'recaptcha:enabled' => 'reCAPTCHA Enabled',
            'recaptcha:secret_key' => 'reCAPTCHA Secret Key',
            'recaptcha:website_key' => 'reCAPTCHA Website Key',
            'kriegerhost:guzzle:timeout' => 'HTTP Request Timeout',
            'kriegerhost:guzzle:connect_timeout' => 'HTTP Connection Timeout',
            'kriegerhost:client_features:allocations:enabled' => 'Auto Create Allocations Enabled',
            'kriegerhost:client_features:allocations:range_start' => 'Starting Port',
            'kriegerhost:client_features:allocations:range_end' => 'Ending Port',
        ];
    }
}
