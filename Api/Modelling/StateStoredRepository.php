<?php

namespace Ecotone\Api\Modelling;

/**
 * licence Apache-2.0
 */
interface StateStoredRepository
{
    public function canHandle(string $aggregateClassName): bool;

    public function findBy(string $aggregateClassName, array $identifiers): ?object;

    /**
     * @param int|null $versionBeforeHandling expected version before command handling, 0 in case there was no aggregate, null in case @Version is not provided
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void;
}
