<?php

namespace Kriegerhost\Tests\Integration\Api\Application\Location;

use Kriegerhost\Models\Node;
use Illuminate\Http\Response;
use Kriegerhost\Models\Location;
use Kriegerhost\Transformers\Api\Application\NodeTransformer;
use Kriegerhost\Transformers\Api\Application\ServerTransformer;
use Kriegerhost\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class LocationControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * Test getting all locations through the API.
     */
    public function testGetLocations()
    {
        $locations = Location::factory()->times(2)->create();

        $response = $this->getJson('/api/application/locations?per_page=60');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [
                ['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']],
                ['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']],
            ],
            'meta' => ['pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages']],
        ]);

        $response
            ->assertJson([
                'object' => 'list',
                'data' => [[], []],
                'meta' => [
                    'pagination' => [
                        'total' => 2,
                        'count' => 2,
                        'per_page' => 60,
                        'current_page' => 1,
                        'total_pages' => 1,
                    ],
                ],
            ])
            ->assertJsonFragment([
                'object' => 'location',
                'attributes' => [
                    'id' => $locations[0]->id,
                    'short' => $locations[0]->short,
                    'long' => $locations[0]->long,
                    'created_at' => $this->formatTimestamp($locations[0]->created_at),
                    'updated_at' => $this->formatTimestamp($locations[0]->updated_at),
                ],
            ])->assertJsonFragment([
                'object' => 'location',
                'attributes' => [
                    'id' => $locations[1]->id,
                    'short' => $locations[1]->short,
                    'long' => $locations[1]->long,
                    'created_at' => $this->formatTimestamp($locations[1]->created_at),
                    'updated_at' => $this->formatTimestamp($locations[1]->updated_at),
                ],
            ]);
    }

    /**
     * Test getting a single location on the API.
     */
    public function testGetSingleLocation()
    {
        $location = Location::factory()->create();

        $response = $this->getJson('/api/application/locations/' . $location->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2);
        $response->assertJsonStructure(['object', 'attributes' => ['id', 'short', 'long', 'created_at', 'updated_at']]);
        $response->assertJson([
            'object' => 'location',
            'attributes' => [
                'id' => $location->id,
                'short' => $location->short,
                'long' => $location->long,
                'created_at' => $this->formatTimestamp($location->created_at),
                'updated_at' => $this->formatTimestamp($location->updated_at),
            ],
        ], true);
    }

    /**
     * Test that all of the defined relationships for a location can be loaded successfully.
     */
    public function testRelationshipsCanBeLoaded()
    {
        $location = Location::factory()->create();
        $server = $this->createServerModel(['user_id' => $this->getApiUser()->id, 'location_id' => $location->id]);

        $response = $this->getJson('/api/application/locations/' . $location->id . '?include=servers,nodes');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2)->assertJsonCount(2, 'attributes.relationships');
        $response->assertJsonStructure([
            'attributes' => [
                'relationships' => [
                    'nodes' => ['object', 'data' => [['attributes' => ['id']]]],
                    'servers' => ['object', 'data' => [['attributes' => ['id']]]],
                ],
            ],
        ]);

        // Just assert that we see the expected relationship IDs in the response.
        $response->assertJson([
            'attributes' => [
                'relationships' => [
                    'nodes' => [
                        'object' => 'list',
                        'data' => [
                            [
                                'object' => 'node',
                                'attributes' => $this->getTransformer(NodeTransformer::class)->transform($server->getRelation('node')),
                            ],
                        ],
                    ],
                    'servers' => [
                        'object' => 'list',
                        'data' => [
                            [
                                'object' => 'server',
                                'attributes' => $this->getTransformer(ServerTransformer::class)->transform($server),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that a relationship that an API key does not have permission to access
     * cannot be loaded onto the model.
     */
    public function testKeyWithoutPermissionCannotLoadRelationship()
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => 0]);

        $location = Location::factory()->create();
        Node::factory()->create(['location_id' => $location->id]);

        $response = $this->getJson('/api/application/locations/' . $location->id . '?include=nodes');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2)->assertJsonCount(1, 'attributes.relationships');
        $response->assertJsonStructure([
            'attributes' => [
                'relationships' => [
                    'nodes' => ['object', 'attributes'],
                ],
            ],
        ]);

        // Just assert that we see the expected relationship IDs in the response.
        $response->assertJson([
            'attributes' => [
                'relationships' => [
                    'nodes' => [
                        'object' => 'null_resource',
                        'attributes' => null,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Test that a missing location returns a 404 error.
     *
     * GET /api/application/locations/:id
     */
    public function testGetMissingLocation()
    {
        $response = $this->getJson('/api/application/locations/nil');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function testErrorReturnedIfNoPermission()
    {
        $location = Location::factory()->create();
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_locations' => 0]);

        $response = $this->getJson('/api/application/locations/' . $location->id);
        $this->assertAccessDeniedJson($response);
    }

    /**
     * Test that a location's existence is not exposed unless an API key has permission
     * to access the resource.
     */
    public function testResourceIsNotExposedWithoutPermissions()
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_locations' => 0]);

        $response = $this->getJson('/api/application/locations/nil');
        $this->assertAccessDeniedJson($response);
    }
}
