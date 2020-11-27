<?php
declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Annotation\Environment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;

#[System]
#[Environment(["prod"])]
class SystemContextWithClassEnvironment
{
    #[Extension]
    public function someAction() : void
    {

    }
}