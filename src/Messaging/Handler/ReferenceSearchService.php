<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Psr\Container\ContainerInterface;

/**
 * Interface ObjectDefinitionService
 * @package Ecotone\Messaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReferenceSearchService extends ContainerInterface
{
    /** This is needed for cases where alias must be added for service */
    public const POSSIBLE_REFERENCE_SUFFIX = '-proxy';

    /**
     * Searching for service with passed reference name
     *
     * @param string $referenceName
     * @return object
     * @throws ReferenceNotFoundException if service with passed reference was not found
     */
    public function get(string $referenceName): object;

    public function has(string $referenceName): bool;
}
