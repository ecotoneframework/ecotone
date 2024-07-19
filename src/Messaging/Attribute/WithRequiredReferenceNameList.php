<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

/**
 * Interface WithRequiredReferenceNameList
 * @package Ecotone\Messaging\Attribute
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface WithRequiredReferenceNameList
{
    /**
     * @return string[]
     */
    public function getRequiredReferenceNameList(): iterable;
}
