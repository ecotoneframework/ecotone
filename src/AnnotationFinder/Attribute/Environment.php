<?php

namespace Ecotone\AnnotationFinder\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Environment
{
    /**
     * @var string[]
     */
    private array $names = [];

    public function __construct(array $environments)
    {
        $this->names = $environments;
    }

    public function getNames(): array
    {
        return $this->names;
    }
}
