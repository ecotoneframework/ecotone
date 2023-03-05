<?php

declare(strict_types=1);

namespace Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\AbstractClass;

use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\MessageEndpoint;
use Test\Ecotone\AnnotationFinder\Fixture\Usage\Attribute\Annotation\SomeHandlerAnnotation;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestCommand;

abstract class TestAbstractHandler
{
    #[SomeHandlerAnnotation]
    public function execute(TestCommand $command): int
    {
        return $command->amount;
    }
}