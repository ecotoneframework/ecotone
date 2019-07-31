<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

/**
 * Interface WithRequiredReferenceNameList
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface WithRequiredReferenceNameList
{
    /**
     * @return string[]
     */
    public function getRequiredReferenceNameList() : iterable;
}