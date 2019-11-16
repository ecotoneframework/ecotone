<?php


namespace Ecotone\Modelling;

/**
 * Interface EventSourcedRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EventSourcedRepository
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
     * @return array|null returns null if event stream was not found or events otherwise
     */
    public function findBy(string $aggregateClassName, array $identifiers) : ?array;

    /**
     * @param array $identifiers
     * @param array $events
     * @param array $metadata
     * @param int|null $expectedVersion if optimistic locking in enabled current version + 1
     */
    public function save(array $identifiers, array $events, array $metadata, ?int $expectedVersion): void;
}