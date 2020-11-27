<?php

namespace Ecotone\AnnotationFinder\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Environment
{
    /**
     * @var string[]
     */
    public array $names = [];

    public function __construct(array $environments = [])
    {
        if (isset($environments['value'])) {
            $environments = $environments['value'];
        }

        $this->names = $environments;
    }
}