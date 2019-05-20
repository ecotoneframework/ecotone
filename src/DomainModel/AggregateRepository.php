<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface AggregateRepository
 * @package SimplyCodedSoftware\DomainModel
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
     * @param string $aggregateClassName
     * @param array  $identifiers
     * @param int    $expectedVersion
     *
     * @return object|null
     */
    public function findWithLockingBy(string $aggregateClassName, array $identifiers, int $expectedVersion);

    /**
     * @param Message $requestMessage
     * @param array   $identifiers
     * @param object  $aggregate
     */
    public function save(Message $requestMessage, array $identifiers, $aggregate): void;
}