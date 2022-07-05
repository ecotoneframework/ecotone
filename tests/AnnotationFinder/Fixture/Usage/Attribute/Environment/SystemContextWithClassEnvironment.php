<?php
declare(strict_types=1);

namespace Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Attribute\Environment;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Ecotone\Tests\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;

#[System]
#[Environment(["prod"])]
class SystemContextWithClassEnvironment
{
    #[Extension]
    public function someAction() : void
    {

    }
}