<?php

namespace Kriegerhost\Tests\Integration\Services\Servers;

use Mockery;
use Kriegerhost\Models\Egg;
use GuzzleHttp\Psr7\Request;
use Kriegerhost\Models\Node;
use Kriegerhost\Models\User;
use GuzzleHttp\Psr7\Response;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Location;
use Kriegerhost\Models\Allocation;
use Illuminate\Foundation\Testing\WithFaker;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Validation\ValidationException;
use Kriegerhost\Models\Objects\DeploymentObject;
use Kriegerhost\Tests\Integration\IntegrationTestCase;
use Kriegerhost\Services\Servers\ServerCreationService;
use Kriegerhost\Repositories\Wings\DaemonServerRepository;
use Kriegerhost\Exceptions\Http\Connection\DaemonConnectionException;

class ServerCreationServiceTest extends IntegrationTestCase
{
    use WithFaker;

    /** @var \Mockery\MockInterface */
    private $daemonServerRepository;

    /**
     * Stub the calls to Wings so that we don't actually hit those API endpoints.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->daemonServerRepository = Mockery::mock(DaemonServerRepository::class);
        $this->swap(DaemonServerRepository::class, $this->daemonServerRepository);
    }

    /**
     * Test that a server can be created when a deployment object is provided to the service.
     *
     * This doesn't really do anything super complicated, we'll rely on other more specific
     * tests to cover that the logic being used does indeed find suitable nodes and ports. For
     * this test we just care that it is recognized and passed off to those functions.
     */
    public function testServerIsCreatedWithDeploymentObject()
    {
        /** @var \Kriegerhost\Models\User $user */
        $user = User::factory()->create();

        /** @var \Kriegerhost\Models\Location $location */
        $location = Location::factory()->create();

        /** @var \Kriegerhost\Models\Node $node */
        $node = Node::factory()->create([
            'location_id' => $location->id,
        ]);

        /** @var \Kriegerhost\Models\Allocation[]|\Illuminate\Database\Eloquent\Collection $allocations */
        $allocations = Allocation::factory()->times(5)->create([
            'node_id' => $node->id,
        ]);

        $deployment = (new DeploymentObject())->setDedicated(true)->setLocations([$node->location_id])->setPorts([
            $allocations[0]->port,
        ]);

        /** @noinspection PhpParamsInspection */
        $egg = $this->cloneEggAndVariables(Egg::query()->findOrFail(1));
        // We want to make sure that the validator service runs as an admin, and not as a regular
        // user when saving variables.
        $egg->variables()->first()->update([
            'user_editable' => false,
        ]);

        $data = [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'owner_id' => $user->id,
            'memory' => 256,
            'swap' => 128,
            'disk' => 100,
            'io' => 500,
            'cpu' => 0,
            'startup' => 'java server2.jar',
            'image' => 'java:8',
            'egg_id' => $egg->id,
            'allocation_additional' => [
                $allocations[4]->id,
            ],
            'environment' => [
                'BUNGEE_VERSION' => '123',
                'SERVER_JARFILE' => 'server2.jar',
            ],
        ];

        $this->daemonServerRepository->expects('setServer')->andReturnSelf();
        $this->daemonServerRepository->expects('create')->with(Mockery::on(function ($value) {
            $this->assertIsArray($value);
            // Just check for some keys to make sure we're getting the expected configuration
            // structure back. Other tests exist to confirm it is the correct structure.
            $this->assertArrayHasKey('uuid', $value);
            $this->assertArrayHasKey('environment', $value);
            $this->assertArrayHasKey('invocation', $value);

            return true;
        }))->andReturnUndefined();

        try {
            $this->getService()->handle(array_merge($data, [
                'environment' => [
                    'BUNGEE_VERSION' => '',
                    'SERVER_JARFILE' => 'server2.jar',
                ],
            ]), $deployment);
            $this->assertTrue(false, 'This statement should not be reached.');
        } catch (ValidationException $exception) {
            $this->assertCount(1, $exception->errors());
            $this->assertArrayHasKey('environment.BUNGEE_VERSION', $exception->errors());
            $this->assertSame('The Bungeecord Version variable field is required.', $exception->errors()['environment.BUNGEE_VERSION'][0]);
        }

        $response = $this->getService()->handle($data, $deployment);

        $this->assertInstanceOf(Server::class, $response);
        $this->assertNotNull($response->uuid);
        $this->assertSame($response->uuidShort, substr($response->uuid, 0, 8));
        $this->assertSame($egg->id, $response->egg_id);
        $this->assertCount(2, $response->variables);
        $this->assertSame('123', $response->variables[0]->server_value);
        $this->assertSame('server2.jar', $response->variables[1]->server_value);

        foreach ($data as $key => $value) {
            if (in_array($key, ['allocation_additional', 'environment'])) {
                continue;
            }

            $this->assertSame($value, $response->{$key});
        }

        $this->assertCount(2, $response->allocations);
        $this->assertSame($response->allocation_id, $response->allocations[0]->id);
        $this->assertSame($allocations[0]->id, $response->allocations[0]->id);
        $this->assertSame($allocations[4]->id, $response->allocations[1]->id);

        $this->assertFalse($response->isSuspended());
        $this->assertTrue($response->oom_disabled);
        $this->assertSame(0, $response->database_limit);
        $this->assertSame(0, $response->allocation_limit);
        $this->assertSame(0, $response->backup_limit);
    }

    /**
     * Test that a server is deleted from the Panel if Wings returns an error during the creation
     * process.
     */
    public function testErrorEncounteredByWingsCausesServerToBeDeleted()
    {
        /** @var \Kriegerhost\Models\User $user */
        $user = User::factory()->create();

        /** @var \Kriegerhost\Models\Location $location */
        $location = Location::factory()->create();

        /** @var \Kriegerhost\Models\Node $node */
        $node = Node::factory()->create([
            'location_id' => $location->id,
        ]);

        /** @var \Kriegerhost\Models\Allocation $allocation */
        $allocation = Allocation::factory()->create([
            'node_id' => $node->id,
        ]);

        $data = [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'owner_id' => $user->id,
            'allocation_id' => $allocation->id,
            'node_id' => $allocation->node_id,
            'memory' => 256,
            'swap' => 128,
            'disk' => 100,
            'io' => 500,
            'cpu' => 0,
            'startup' => 'java server2.jar',
            'image' => 'java:8',
            'egg_id' => 1,
            'environment' => [
                'BUNGEE_VERSION' => '123',
                'SERVER_JARFILE' => 'server2.jar',
            ],
        ];

        $this->daemonServerRepository->expects('setServer->create')->andThrows(
            new DaemonConnectionException(
                new BadResponseException('Bad request', new Request('POST', '/create'), new Response(500))
            )
        );

        $this->daemonServerRepository->expects('setServer->delete')->andReturnUndefined();

        $this->expectException(DaemonConnectionException::class);

        $this->getService()->handle($data);

        $this->assertDatabaseMissing('servers', ['owner_id' => $user->id]);
    }

    /**
     * @return \Kriegerhost\Services\Servers\ServerCreationService
     */
    private function getService()
    {
        return $this->app->make(ServerCreationService::class);
    }
}
