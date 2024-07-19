<?php

declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\AbstractClass;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestCommand;

/**
 * licence Apache-2.0
 */
abstract class TestAbstractHandler
{
    #[SomeHandlerAnnotation]
    public function execute(TestCommand $command): int
    {
        return $command->amount;
    }
}
