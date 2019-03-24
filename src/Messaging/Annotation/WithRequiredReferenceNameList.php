<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;

/**
 * Interface WithRequiredReferenceNameList
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface WithRequiredReferenceNameList
{
    /**
     * @return string[]
     */
    public function getRequiredReferenceNameList() : iterable;
}