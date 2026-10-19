<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Messaging\Gateway\MessagingEntrypointService;

use function implode;

use InvalidArgumentException;

/**
 * licence Apache-2.0
 */
final class ProjectionOperations
{
    public function __construct(
        private ProjectionRegistry $registry,
        private MessagingEntrypointService $messagingEntrypoint,
    ) {
    }

    public function initialize(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::INITIALIZE);
    }

    public function initializeAll(): void
    {
        foreach ($this->registry->projectionNames() as $projectionName) {
            $this->initialize($projectionName);
        }
    }

    public function trigger(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::TRIGGER);
    }

    public function reset(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::RESET);
    }

    public function backfill(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::BACKFILL);
    }

    public function rebuild(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::REBUILD);
    }

    public function delete(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::DELETE);
    }

    private function run(string $projectionName, string $operation): void
    {
        if (! $this->registry->has($projectionName)) {
            $registered = implode(', ', [...$this->registry->projectionNames()]);

            throw new InvalidArgumentException("There is no projection with name {$projectionName}. Registered projections: [{$registered}]. Use the name given in #[Projection] of the projection to {$operation}.");
        }

        $this->messagingEntrypoint->send([], ProjectionOperationEndpoint::channelFor($projectionName, $operation));
    }
}
