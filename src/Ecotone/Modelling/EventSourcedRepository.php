<?php


namespace Ecotone\Modelling;

/**
 * Interface EventSourcedRepository
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface EventSourcedRepository
{
    public function canHandle(string $aggregateClassName): bool;

    public function findBy(string $aggregateClassName, array $identifiers) : EventStream;

    /**
     * @param int $versionBeforeHandling expected version before command handling, 0 in case there was no aggregate
     */
    public function save(array $identifiers, string $aggregateClassName, array $events, array $metadata, int $versionBeforeHandling): void;
}