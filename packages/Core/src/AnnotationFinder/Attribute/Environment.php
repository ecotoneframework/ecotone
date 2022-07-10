<?php

namespace Ecotone\AnnotationFinder\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
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