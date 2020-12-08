<?php


namespace Ecotone\Modelling\Annotation;

#[\Attribute]
class Distributed
{
    private string $distributionReference;

    public function __construct(string $distributionReference = "default")
    {
        $this->distributionReference = $distributionReference;
    }

    public function getDistributionReference(): string
    {
        return $this->distributionReference;
    }
}