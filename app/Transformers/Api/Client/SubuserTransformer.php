<?php

namespace Kriegerhost\Transformers\Api\Client;

use Kriegerhost\Models\Subuser;

class SubuserTransformer extends BaseClientTransformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * Transforms a subuser into a model that can be shown to a front-end user.
     *
     * @return array|void
     *
     * @throws \Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function transform(Subuser $model)
    {
        return array_merge(
            $this->makeTransformer(UserTransformer::class)->transform($model->user),
            ['permissions' => $model->permissions]
        );
    }
}
