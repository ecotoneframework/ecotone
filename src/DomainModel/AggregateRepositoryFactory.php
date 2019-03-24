<?php

namespace SimplyCodedSoftware\DomainModel;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Interface AggregateRepositoryFactory
 * @package SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface AggregateRepositoryFactory
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string $aggregateClassName
     *
     * @return bool
     */
    public function canHandle(ReferenceSearchService $referenceSearchService, string $aggregateClassName) : bool;

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @param string $aggregateClassName
     *
     * @return AggregateRepository
     */
    public function getFor(ReferenceSearchService $referenceSearchService, string $aggregateClassName) : AggregateRepository;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;
}