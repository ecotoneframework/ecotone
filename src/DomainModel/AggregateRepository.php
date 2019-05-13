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
     * @param array $identifiers
     *
     * @return object|null
     */
    public function findBy(array $identifiers);

    /**
     * @param array $identifiers
     * @param int $expectedVersion
     *
     * @return object|null
     */
    public function findWithLockingBy(array $identifiers, int $expectedVersion);

    /**
     * @param Message $requestMessage
     * @param object $aggregate
     */
    public function save(Message $requestMessage, $aggregate): void;
}