<?php

namespace Kriegerhost\Services\Allocations;

use Webmozart\Assert\Assert;
use Kriegerhost\Models\Server;
use Kriegerhost\Models\Allocation;
use Kriegerhost\Exceptions\Service\Allocation\AutoAllocationNotEnabledException;
use Kriegerhost\Exceptions\Service\Allocation\NoAutoAllocationSpaceAvailableException;

class FindAssignableAllocationService
{
    /**
     * @var \Kriegerhost\Services\Allocations\AssignmentService
     */
    private $service;

    /**
     * FindAssignableAllocationService constructor.
     *
     * @param \Kriegerhost\Services\Allocations\AssignmentService $service
     */
    public function __construct(AssignmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Finds an existing unassigned allocation and attempts to assign it to the given server. If
     * no allocation can be found, a new one will be created with a random port between the defined
     * range from the configuration.
     *
     * @return \Kriegerhost\Models\Allocation
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    public function handle(Server $server)
    {
        if (!config('kriegerhost.client_features.allocations.enabled')) {
            throw new AutoAllocationNotEnabledException();
        }

        // Attempt to find a given available allocation for a server. If one cannot be found
        // we will fall back to attempting to create a new allocation that can be used for the
        // server.
        /** @var \Kriegerhost\Models\Allocation|null $allocation */
        $allocation = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->whereNull('server_id')
            ->inRandomOrder()
            ->first();

        $allocation = $allocation ?? $this->createNewAllocation($server);

        $allocation->update(['server_id' => $server->id]);

        return $allocation->refresh();
    }

    /**
     * Create a new allocation on the server's node with a random port from the defined range
     * in the settings. If there are no matches in that range, or something is wrong with the
     * range information provided an exception will be raised.
     *
     * @throws \Kriegerhost\Exceptions\DisplayException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\CidrOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\InvalidPortMappingException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\PortOutOfRangeException
     * @throws \Kriegerhost\Exceptions\Service\Allocation\TooManyPortsInRangeException
     */
    protected function createNewAllocation(Server $server): Allocation
    {
        $start = config('kriegerhost.client_features.allocations.range_start', null);
        $end = config('kriegerhost.client_features.allocations.range_end', null);

        if (!$start || !$end) {
            throw new NoAutoAllocationSpaceAvailableException();
        }

        Assert::integerish($start);
        Assert::integerish($end);

        // Get all of the currently allocated ports for the node so that we can figure out
        // which port might be available.
        $ports = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->whereBetween('port', [$start, $end])
            ->pluck('port');

        // Compute the difference of the range and the currently created ports, finding
        // any port that does not already exist in the database. We will then use this
        // array of ports to create a new allocation to assign to the server.
        $available = array_diff(range($start, $end), $ports->toArray());

        // If we've already allocated all of the ports, just abort.
        if (empty($available)) {
            throw new NoAutoAllocationSpaceAvailableException();
        }

        // Pick a random port out of the remaining available ports.
        /** @var int $port */
        $port = $available[array_rand($available)];

        $this->service->handle($server->node, [
            'allocation_ip' => $server->allocation->ip,
            'allocation_ports' => [$port],
        ]);

        /** @var \Kriegerhost\Models\Allocation $allocation */
        $allocation = $server->node->allocations()
            ->where('ip', $server->allocation->ip)
            ->where('port', $port)
            ->firstOrFail();

        return $allocation;
    }
}
