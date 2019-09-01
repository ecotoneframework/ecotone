<?php

namespace Ecotone\Modelling;

/**
 * Interface AggregateRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepository
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
    public function findBy(string $aggregateClassName, array $identifiers);

    /**
     * @param array $identifiers
     * @param object $aggregate
     * @param array $metadata
     * @param int|null $expectedVersion if optimistic locking in enabled current version + 1
     */
    public function save(array $identifiers, $aggregate, array $metadata, ?int $expectedVersion): void;
}