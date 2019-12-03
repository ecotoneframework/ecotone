<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

/**
 * Interface ObjectDefinitionService
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReferenceSearchService
{
    /**
     * Searching for service with passed reference name
     *
     * @param string $referenceName
     * @return object
     * @throws ReferenceNotFoundException if service with passed reference was not found
     */
    public function get(string $referenceName) : object;

    public function has(string $referenceName) : bool;
}