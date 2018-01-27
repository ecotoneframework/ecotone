<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Interface ObjectDefinitionService
 * @package SimplyCodedSoftware\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReferenceSearchService
{
    /**
     * Searching for service with passed reference name
     *
     * @param string $reference
     * @return object
     * @throws ReferenceNotFoundException if service with passed reference was not found
     */
    public function findByReference(string $reference);
}