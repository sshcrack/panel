<?php

namespace Kriegerhost\Transformers\Api\Client;

use Kriegerhost\Models\User;
use Webmozart\Assert\Assert;
use Kriegerhost\Models\Server;
use Illuminate\Container\Container;
use Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException;
use Kriegerhost\Transformers\Api\Application\BaseTransformer as BaseApplicationTransformer;

abstract class BaseClientTransformer extends BaseApplicationTransformer
{
    /**
     * @var \Kriegerhost\Models\User
     */
    private $user;

    /**
     * Return the user model of the user requesting this transformation.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set the user model of the user requesting this transformation.
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the API key loaded onto the transformer has permission
     * to access a different resource. This is used when including other
     * models on a transformation request.
     *
     * @param \Kriegerhost\Models\Server $server
     */
    protected function authorize(string $ability, Server $server = null): bool
    {
        Assert::isInstanceOf($server, Server::class);

        return $this->getUser()->can($ability, [$server]);
    }

    /**
     * Create a new instance of the transformer and pass along the currently
     * set API key.
     *
     * @return self
     *
     * @throws \Kriegerhost\Exceptions\Transformer\InvalidTransformerLevelException
     */
    protected function makeTransformer(string $abstract, array $parameters = [])
    {
        /** @var \Kriegerhost\Transformers\Api\Application\BaseTransformer $transformer */
        $transformer = Container::getInstance()->makeWith($abstract, $parameters);
        $transformer->setKey($this->getKey());

        if (!$transformer instanceof self) {
            throw new InvalidTransformerLevelException('Calls to ' . __METHOD__ . ' must return a transformer that is an instance of ' . __CLASS__);
        }

        return $transformer;
    }
}
