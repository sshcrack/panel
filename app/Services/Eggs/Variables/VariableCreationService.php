<?php

namespace Kriegerhost\Services\Eggs\Variables;

use Kriegerhost\Models\EggVariable;
use Illuminate\Contracts\Validation\Factory;
use Kriegerhost\Traits\Services\ValidatesValidationRules;
use Kriegerhost\Contracts\Repository\EggVariableRepositoryInterface;
use Kriegerhost\Exceptions\Service\Egg\Variable\ReservedVariableNameException;

class VariableCreationService
{
    use ValidatesValidationRules;

    /**
     * @var \Kriegerhost\Contracts\Repository\EggVariableRepositoryInterface
     */
    private $repository;

    /**
     * @var \Illuminate\Contracts\Validation\Factory
     */
    private $validator;

    /**
     * VariableCreationService constructor.
     */
    public function __construct(EggVariableRepositoryInterface $repository, Factory $validator)
    {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    /**
     * Return the validation factory instance to be used by rule validation
     * checking in the trait.
     */
    protected function getValidator(): Factory
    {
        return $this->validator;
    }

    /**
     * Create a new variable for a given Egg.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \Kriegerhost\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function handle(int $egg, array $data): EggVariable
    {
        if (in_array(strtoupper(array_get($data, 'env_variable')), explode(',', EggVariable::RESERVED_ENV_NAMES))) {
            throw new ReservedVariableNameException(sprintf('Cannot use the protected name %s for this environment variable.', array_get($data, 'env_variable')));
        }

        if (!empty($data['rules'] ?? '')) {
            $this->validateRules($data['rules']);
        }

        $options = array_get($data, 'options') ?? [];

        return $this->repository->create([
            'egg_id' => $egg,
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'env_variable' => $data['env_variable'] ?? '',
            'default_value' => $data['default_value'] ?? '',
            'user_viewable' => in_array('user_viewable', $options),
            'user_editable' => in_array('user_editable', $options),
            'rules' => $data['rules'] ?? '',
        ]);
    }
}
