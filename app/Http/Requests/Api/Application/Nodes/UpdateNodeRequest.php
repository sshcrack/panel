<?php

namespace Kriegerhost\Http\Requests\Api\Application\Nodes;

use Kriegerhost\Models\Node;

class UpdateNodeRequest extends StoreNodeRequest
{
    /**
     * Apply validation rules to this request. Uses the parent class rules()
     * function but passes in the rules for updating rather than creating.
     */
    public function rules(array $rules = null): array
    {
        $nodeId = $this->getModel(Node::class)->id;

        return parent::rules(Node::getRulesForUpdate($nodeId));
    }
}
