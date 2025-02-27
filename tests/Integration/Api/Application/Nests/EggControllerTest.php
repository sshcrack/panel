<?php

namespace Kriegerhost\Tests\Integration\Api\Application\Nests;

use Illuminate\Support\Arr;
use Illuminate\Http\Response;
use Kriegerhost\Contracts\Repository\EggRepositoryInterface;
use Kriegerhost\Transformers\Api\Application\EggTransformer;
use Kriegerhost\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class EggControllerTest extends ApplicationApiIntegrationTestCase
{
    /**
     * @var \Kriegerhost\Contracts\Repository\EggRepositoryInterface
     */
    private $repository;

    /**
     * Setup tests.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(EggRepositoryInterface::class);
    }

    /**
     * Test that all of the eggs belonging to a given nest can be returned.
     */
    public function testListAllEggsInNest()
    {
        $eggs = $this->repository->findWhere([['nest_id', '=', 1]]);

        $response = $this->getJson('/api/application/nests/' . $eggs->first()->nest_id . '/eggs');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(count($eggs), 'data');
        $response->assertJsonStructure([
            'object',
            'data' => [
                [
                    'object',
                    'attributes' => [
                        'id', 'uuid', 'nest', 'author', 'description', 'docker_image', 'startup', 'created_at', 'updated_at',
                        'script' => ['privileged', 'install', 'entry', 'container', 'extends'],
                        'config' => [
                            'files' => [],
                            'startup' => ['done'],
                            'stop',
                            'logs' => ['custom', 'location'],
                            'extends',
                        ],
                    ],
                ],
            ],
        ]);

        foreach (array_get($response->json(), 'data') as $datum) {
            $egg = $eggs->where('id', '=', $datum['attributes']['id'])->first();

            $expected = json_encode(Arr::sortRecursive($datum['attributes']));
            $actual = json_encode(Arr::sortRecursive($this->getTransformer(EggTransformer::class)->transform($egg)));

            $this->assertSame(
                $expected,
                $actual,
                'Unable to find JSON fragment: ' . PHP_EOL . PHP_EOL . "[{$expected}]" . PHP_EOL . PHP_EOL . 'within' . PHP_EOL . PHP_EOL . "[{$actual}]."
            );
        }
    }

    /**
     * Test that a single egg can be returned.
     */
    public function testReturnSingleEgg()
    {
        $egg = $this->repository->find(1);

        $response = $this->getJson('/api/application/nests/' . $egg->nest_id . '/eggs/' . $egg->id);
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'id', 'uuid', 'nest', 'author', 'description', 'docker_image', 'startup', 'script' => [], 'config' => [], 'created_at', 'updated_at',
            ],
        ]);

        $response->assertJson([
            'object' => 'egg',
            'attributes' => $this->getTransformer(EggTransformer::class)->transform($egg),
        ], true);
    }

    /**
     * Test that a single egg and all of the defined relationships can be returned.
     */
    public function testReturnSingleEggWithRelationships()
    {
        $egg = $this->repository->find(1);

        $response = $this->getJson('/api/application/nests/' . $egg->nest_id . '/eggs/' . $egg->id . '?include=servers,variables,nest');
        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'object',
            'attributes' => [
                'relationships' => [
                    'nest' => ['object', 'attributes'],
                    'servers' => ['object', 'data' => []],
                    'variables' => ['object', 'data' => []],
                ],
            ],
        ]);
    }

    /**
     * Test that a missing egg returns a 404 error.
     */
    public function testGetMissingEgg()
    {
        $egg = $this->repository->find(1);

        $response = $this->getJson('/api/application/nests/' . $egg->nest_id . '/eggs/nil');
        $this->assertNotFoundJson($response);
    }

    /**
     * Test that an authentication error occurs if a key does not have permission
     * to access a resource.
     */
    public function testErrorReturnedIfNoPermission()
    {
        $egg = $this->repository->find(1);
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_eggs' => 0]);

        $response = $this->getJson('/api/application/nests/' . $egg->nest_id . '/eggs');
        $this->assertAccessDeniedJson($response);
    }

    /**
     * Test that a nests's existence is not exposed unless an API key has permission
     * to access the resource.
     */
    public function testResourceIsNotExposedWithoutPermissions()
    {
        $egg = $this->repository->find(1);
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_eggs' => 0]);

        $response = $this->getJson('/api/application/nests/' . $egg->nest_id . '/eggs/nil');
        $this->assertAccessDeniedJson($response);
    }
}
