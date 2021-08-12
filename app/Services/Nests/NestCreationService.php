<?php

namespace Kriegerhost\Services\Nests;

use Ramsey\Uuid\Uuid;
use Kriegerhost\Models\Nest;
use Kriegerhost\Contracts\Repository\NestRepositoryInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class NestCreationService
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var \Kriegerhost\Contracts\Repository\NestRepositoryInterface
     */
    private $repository;

    /**
     * NestCreationService constructor.
     */
    public function __construct(ConfigRepository $config, NestRepositoryInterface $repository)
    {
        $this->config = $config;
        $this->repository = $repository;
    }

    /**
     * Create a new nest on the system.
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function handle(array $data, string $author = null): Nest
    {
        return $this->repository->create([
            'uuid' => Uuid::uuid4()->toString(),
            'author' => $author ?? $this->config->get('kriegerhost.service.author'),
            'name' => array_get($data, 'name'),
            'description' => array_get($data, 'description'),
        ], true, true);
    }
}
