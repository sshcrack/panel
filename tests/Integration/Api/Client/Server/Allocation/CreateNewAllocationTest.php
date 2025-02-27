<?php

namespace Kriegerhost\Tests\Integration\Api\Client\Server\Allocation;

use Illuminate\Http\Response;
use Kriegerhost\Models\Allocation;
use Kriegerhost\Models\Permission;
use Kriegerhost\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class CreateNewAllocationTest extends ClientApiIntegrationTestCase
{
    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        config()->set('kriegerhost.client_features.allocations.enabled', true);
        config()->set('kriegerhost.client_features.allocations.range_start', 5000);
        config()->set('kriegerhost.client_features.allocations.range_end', 5050);
    }

    /**
     * Tests that a new allocation can be properly assigned to a server.
     *
     * @dataProvider permissionDataProvider
     */
    public function testNewAllocationCanBeAssignedToServer(array $permission)
    {
        /** @var \Kriegerhost\Models\Server $server */
        [$user, $server] = $this->generateTestAccount($permission);
        $server->update(['allocation_limit' => 2]);

        $response = $this->actingAs($user)->postJson($this->link($server, '/network/allocations'));
        $response->assertJsonPath('object', Allocation::RESOURCE_NAME);

        $matched = Allocation::query()->findOrFail($response->json('attributes.id'));

        $this->assertSame($server->id, $matched->server_id);
        $this->assertJsonTransformedWith($response->json('attributes'), $matched);
    }

    /**
     * Test that a user without the required permissions cannot create an allocation for
     * the server instance.
     */
    public function testAllocationCannotBeCreatedIfUserDoesNotHavePermission()
    {
        /** @var \Kriegerhost\Models\Server $server */
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_ALLOCATION_UPDATE]);
        $server->update(['allocation_limit' => 2]);

        $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))->assertForbidden();
    }

    /**
     * Test that an error is returned to the user if this feature is not enabled on the system.
     */
    public function testAllocationCannotBeCreatedIfNotEnabled()
    {
        config()->set('kriegerhost.client_features.allocations.enabled', false);

        /** @var \Kriegerhost\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();
        $server->update(['allocation_limit' => 2]);

        $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.code', 'AutoAllocationNotEnabledException')
            ->assertJsonPath('errors.0.detail', 'Server auto-allocation is not enabled for this instance.');
    }

    /**
     * Test that an allocation cannot be created if the server has reached it's allocation limit.
     */
    public function testAllocationCannotBeCreatedIfServerIsAtLimit()
    {
        /** @var \Kriegerhost\Models\Server $server */
        [$user, $server] = $this->generateTestAccount();
        $server->update(['allocation_limit' => 1]);

        $this->actingAs($user)->postJson($this->link($server, '/network/allocations'))
            ->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonPath('errors.0.code', 'DisplayException')
            ->assertJsonPath('errors.0.detail', 'Cannot assign additional allocations to this server: limit has been reached.');
    }

    /**
     * @return array
     */
    public function permissionDataProvider()
    {
        return [[[Permission::ACTION_ALLOCATION_CREATE]], [[]]];
    }
}
