<?php

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ClassReference
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public string $referenceName = "";
}