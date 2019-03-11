<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config;

use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;

/**
 * Interface ReferenceTypeFromNameExtractor
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReferenceTypeFromNameResolver
{
    const REFERENCE_NAME = "referenceTypeFromNameExtractor";

    /**
     * @param string $referenceName
     * @return TypeDescriptor
     * @throws ConfigurationException if not found used reference
     */
    public function resolve(string $referenceName) : TypeDescriptor;
}