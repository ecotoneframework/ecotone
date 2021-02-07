<?php
declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Attribute\Environment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;

#[System]
class SystemContextWithMethodMultipleEnvironmentsExample
{
    #[Extension]
    #[Environment(["dev", "prod", "test"])]
    public function configMultipleEnvironments() : array
    {
        return [];
    }
}