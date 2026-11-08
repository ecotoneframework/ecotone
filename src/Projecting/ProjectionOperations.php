<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Api\Messaging\MessageHeaders;
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

    /**
     * @param array<string, mixed> $headers
     */
    public function initialize(string $projectionName, array $headers = []): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::INITIALIZE, $headers);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function initializeAll(array $headers = []): void
    {
        foreach ($this->registry->projectionNames() as $projectionName) {
            $this->initialize($projectionName, $headers);
        }
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function trigger(string $projectionName, array $headers = []): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::TRIGGER, $headers);
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

    public function rebuildTrackedBy(string $projectionName, string $tracingId): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::REBUILD, [MessageHeaders::TRACING_ID => $tracingId]);
    }

    public function backfillTrackedBy(string $projectionName, string $tracingId): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::BACKFILL, [MessageHeaders::TRACING_ID => $tracingId]);
    }

    public function delete(string $projectionName): void
    {
        $this->run($projectionName, ProjectionOperationEndpoint::DELETE);
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function run(string $projectionName, string $operation, array $headers = []): void
    {
        if (! $this->registry->has($projectionName)) {
            $registered = implode(', ', [...$this->registry->projectionNames()]);

            throw new InvalidArgumentException("There is no projection with name {$projectionName}. Registered projections: [{$registered}]. Use the name given in #[Projection] of the projection to {$operation}.");
        }

        $this->messagingEntrypoint->sendWithHeaders([], $headers, ProjectionOperationEndpoint::channelFor($projectionName, $operation));
    }
}
