<?php

declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Environment;

use Ecotone\AnnotationFinder\Attribute\Environment;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\Extension;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\System;

#[System]
#[Environment(['prod'])]
/**
 * licence Apache-2.0
 */
class SystemContextWithClassEnvironment
{
    #[Extension]
    public function someAction(): void
    {
    }
}
