<?php

namespace Kriegerhost\Services\Eggs\Scripts;

use Kriegerhost\Models\Egg;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Exceptions\Service\Egg\InvalidCopyFromException;

class InstallScriptService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * InstallScriptService constructor.
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Modify the install script for a given Egg.
     *
     * @param int|\Kriegerhost\Models\Egg $egg
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\InvalidCopyFromException
     */
    public function handle(Egg $egg, array $data)
    {
        if (!is_null(array_get($data, 'copy_script_from'))) {
            if (!$this->repository->isCopyableScript(array_get($data, 'copy_script_from'), $egg->nest_id)) {
                throw new InvalidCopyFromException(trans('exceptions.nest.egg.invalid_copy_id'));
            }
        }

        $this->repository->withoutFreshModel()->update($egg->id, [
            'script_install' => array_get($data, 'script_install'),
            'script_is_privileged' => array_get($data, 'script_is_privileged', 1),
            'script_entry' => array_get($data, 'script_entry'),
            'script_container' => array_get($data, 'script_container'),
            'copy_script_from' => array_get($data, 'copy_script_from'),
        ]);
    }
}
