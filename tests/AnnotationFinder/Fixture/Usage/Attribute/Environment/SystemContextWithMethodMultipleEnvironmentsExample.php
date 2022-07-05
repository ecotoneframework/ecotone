<?php
declare(strict_types=1);

namespace Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Attribute\Environment;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Tests\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;

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