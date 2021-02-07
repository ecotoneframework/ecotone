<?php
declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Attribute\Environment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;

#[System]
#[Environment(["prod", "dev"])]
class SystemContextWithMethodEnvironmentExample
{
    #[Extension]
    #[Environment(["dev"])]
    public function configSingleEnvironment() : array
    {
        return [];
    }
}