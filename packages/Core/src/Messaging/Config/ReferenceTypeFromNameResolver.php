<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\Type;

/**
 * Interface ReferenceTypeFromNameExtractor
 * @package Ecotone\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ReferenceTypeFromNameResolver
{
    public const REFERENCE_NAME = 'referenceTypeFromNameExtractor';

    /**
     * @param string $referenceName
     * @return Type
     * @throws ReferenceNotFoundException if not found used reference
     */
    public function resolve(string $referenceName): Type;
}
