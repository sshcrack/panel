<?php

namespace Kriegerhost\Services\Nodes;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Kriegerhost\Models\Node;
use Illuminate\Contracts\Encryption\Encrypter;
use Kriegerhost\Contracts\Repository\NodeRepositoryInterface;

class NodeCreationService
{
    /**
     * @var \Kriegerhost\Contracts\Repository\NodeRepositoryInterface
     */
    protected $repository;

    /**
     * @var \Illuminate\Contracts\Encryption\Encrypter
     */
    private $encrypter;

    /**
     * CreationService constructor.
     */
    public function __construct(Encrypter $encrypter, NodeRepositoryInterface $repository)
    {
        $this->repository = $repository;
        $this->encrypter = $encrypter;
    }

    /**
     * Create a new node on the panel.
     *
     * @return \Kriegerhost\Models\Node
     *
     * @throws \Kriegerhost\Exceptions\Model\DataValidationException
     */
    public function handle(array $data)
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['daemon_token'] = $this->encrypter->encrypt(Str::random(Node::DAEMON_TOKEN_LENGTH));
        $data['daemon_token_id'] = Str::random(Node::DAEMON_TOKEN_ID_LENGTH);

        return $this->repository->create($data, true, true);
    }
}
