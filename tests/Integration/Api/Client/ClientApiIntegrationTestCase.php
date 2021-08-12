<?php

namespace Kriegerhost\Tests\Integration\Api\Client;

use ReflectionClass;
use Kriegerhost\Models\Node;
use Kriegerhost\Models\Task;
use Kriegerhost\Models\User;
use Webmozart\Assert\Assert;
use InvalidArgumentException;
use Kriegerhost\Models\Backup;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Subuser;
use Kriegerhost\Models\Database;
use Kriegerhost\Models\Location;
use Kriegerhost\Models\Schedule;
use Illuminate\Support\Collection;
use Kriegerhost\Models\Allocation;
use Kriegerhost\Models\DatabaseHost;
use Kriegerhost\Tests\Integration\TestResponse;
use Kriegerhost\Tests\Integration\IntegrationTestCase;
use Kriegerhost\Transformers\Api\Client\BaseClientTransformer;

abstract class ClientApiIntegrationTestCase extends IntegrationTestCase
{
    /**
     * Cleanup after running tests.
     */
    protected function tearDown(): void
    {
        Database::query()->forceDelete();
        DatabaseHost::query()->forceDelete();
        Backup::query()->forceDelete();
        Server::query()->forceDelete();
        Node::query()->forceDelete();
        Location::query()->forceDelete();
        User::query()->forceDelete();

        parent::tearDown();
    }

    /**
     * Override the default createTestResponse from Illuminate so that we can
     * just dump 500-level errors to the screen in the tests without having
     * to keep re-assigning variables.
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function createTestResponse($response)
    {
        return TestResponse::fromBaseResponse($response);
    }

    /**
     * Returns a link to the specific resource using the client API.
     *
     * @param mixed $model
     * @param string|null $append
     */
    protected function link($model, $append = null): string
    {
        $link = '';
        switch (get_class($model)) {
            case Server::class:
                $link = "/api/client/servers/{$model->uuid}";
                break;
            case Schedule::class:
                $link = "/api/client/servers/{$model->server->uuid}/schedules/{$model->id}";
                break;
            case Task::class:
                $link = "/api/client/servers/{$model->schedule->server->uuid}/schedules/{$model->schedule->id}/tasks/{$model->id}";
                break;
            case Allocation::class:
                $link = "/api/client/servers/{$model->server->uuid}/network/allocations/{$model->id}";
                break;
            case Backup::class:
                $link = "/api/client/servers/{$model->server->uuid}/backups/{$model->uuid}";
                break;
            default:
                throw new InvalidArgumentException(sprintf('Cannot create link for Model of type %s', class_basename($model)));
        }

        return $link . ($append ? '/' . ltrim($append, '/') : '');
    }

    /**
     * Generates a user and a server for that user. If an array of permissions is passed it
     * is assumed that the user is actually a subuser of the server.
     *
     * @param string[] $permissions
     */
    protected function generateTestAccount(array $permissions = []): array
    {
        /** @var \Kriegerhost\Models\User $user */
        $user = User::factory()->create();

        if (empty($permissions)) {
            return [$user, $this->createServerModel(['user_id' => $user->id])];
        }

        /** @var \Kriegerhost\Models\Server $server */
        $server = $this->createServerModel();

        Subuser::query()->create([
            'user_id' => $user->id,
            'server_id' => $server->id,
            'permissions' => $permissions,
        ]);

        return [$user, $server];
    }

    /**
     * Asserts that the data passed through matches the output of the data from the transformer. This
     * will remove the "relationships" key when performing the comparison.
     *
     * @param \Kriegerhost\Models\Model|\Illuminate\Database\Eloquent\Model $model
     */
    protected function assertJsonTransformedWith(array $data, $model)
    {
        $reflect = new ReflectionClass($model);
        $transformer = sprintf('\\Kriegerhost\\Transformers\\Api\\Client\\%sTransformer', $reflect->getShortName());

        $transformer = new $transformer();
        $this->assertInstanceOf(BaseClientTransformer::class, $transformer);

        $this->assertSame(
            $transformer->transform($model),
            Collection::make($data)->except(['relationships'])->toArray()
        );
    }
}
