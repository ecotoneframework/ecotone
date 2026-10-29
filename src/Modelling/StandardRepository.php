<?php

namespace Ecotone\Modelling;

/**
 * @TODO Ecotone 2.0 change to StateStoredRepository
 *
 * Interface AggregateRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface StandardRepository
{
    /**
     * @param string $aggregateClassName
     *
     * @return bool
     */
    public function canHandle(string $aggregateClassName): bool;

    /**
     * @param string $aggregateClassName
     * @param array  $identifiers
     *
     * @return object|null
     */
    public function findBy(string $aggregateClassName, array $identifiers): ?object;

    /**
     * @param array $identifiers
     * @param object $aggregate
     * @param array $metadata
     * @param int|null $versionBeforeHandling expected version before command handling, 0 in case there was no aggregate, null in case @Version is not provided
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $versionBeforeHandling): void;
}
