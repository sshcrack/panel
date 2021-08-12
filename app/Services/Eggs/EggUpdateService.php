<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Eggs;

use Kriegerhost\Models\Egg;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Exceptions\Service\Egg\NoParentConfigurationFoundException;

class EggUpdateService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    protected $repository;

    /**
     * EggUpdateService constructor.
     */
    public function __construct(EggRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a service option.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     * @throws \Kriegerhost\Exceptions\Service\Egg\NoParentConfigurationFoundException
     */
    public function handle(Egg $egg, array $data)
    {
        if (!is_null(array_get($data, 'config_from'))) {
            $results = $this->repository->findCountWhere([
                ['nest_id', '=', $egg->nest_id],
                ['id', '=', array_get($data, 'config_from')],
            ]);

            if ($results !== 1) {
                throw new NoParentConfigurationFoundException(trans('exceptions.nest.egg.must_be_child'));
            }
        }

        // TODO(dane): Once the admin UI is done being reworked and this is exposed
        //  in said UI, remove this so that you can actually update the denylist.
        unset($data['file_denylist']);

        $this->repository->withoutFreshModel()->update($egg->id, $data);
    }
}
