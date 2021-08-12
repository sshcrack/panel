<?php
/**
 * Kriegerhost - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Kriegerhost\Services\Nests;

use Kriegerhost\Contracts\Repository\NestRepositoryInterface;

class NestUpdateService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    protected $repository;

    /**
     * NestUpdateService constructor.
     */
    public function __construct(NestRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Update a nest and prevent changing the author once it is set.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     * @throws \Kriegerhost\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(int $nest, array $data)
    {
        if (!is_null(array_get($data, 'author'))) {
            unset($data['author']);
        }

        $this->repository->withoutFreshModel()->update($nest, $data);
    }
}
