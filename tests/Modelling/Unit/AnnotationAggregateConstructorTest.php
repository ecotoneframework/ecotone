<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsCommandHandler;
use Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsEventHandler;
use Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsQueryHandler;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class AnnotationAggregateConstructorTest extends TestCase
{
    public function test_aggregate_cannot_have_constructor_being_annotated_as_command_handler(): void
    {
        $this->expectExceptionObject(InvalidArgumentException::create('Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsCommandHandler::__construct cannot be annotated as command handler'));

        EcotoneLite::bootstrapFlowTesting([ConstructorAsCommandHandler::class]);
    }

    public function test_aggregate_cannot_have_constructor_being_annotated_as_event_handler(): void
    {
        $this->expectExceptionObject(InvalidArgumentException::create('Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsEventHandler::__construct cannot be annotated as event handler'));

        EcotoneLite::bootstrapFlowTesting([ConstructorAsEventHandler::class]);
    }

    public function test_aggregate_cannot_have_constructor_being_annotated_as_query_handler(): void
    {
        $this->expectExceptionObject(InvalidArgumentException::create('Test\Ecotone\Modelling\Fixture\AnnotatedConstructor\ConstructorAsQueryHandler::__construct cannot be annotated as query handler'));

        EcotoneLite::bootstrapFlowTesting([ConstructorAsQueryHandler::class]);
    }
}
