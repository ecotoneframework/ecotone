<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions\RandomEvent;
use Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions\SagaWithMultipleEventHandlers;
use Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions\SagaWithMultipleEventHandlersAndFactoryMethod;

/**
 * @internal
 */
final class SagaTest extends TestCase
{
    public function test_throwing_exception_if_registering_multiple_actions_with_factory_method(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [SagaWithMultipleEventHandlersAndFactoryMethod::class]
        );
    }

    public function test_handling_saga_with_multiple_action_methods_for_same_event(): void
    {
        $saga = EcotoneLite::bootstrapFlowTesting(
            [SagaWithMultipleEventHandlers::class]
        )
            ->publishEvent(new RandomEvent('123'))
            ->getSaga(SagaWithMultipleEventHandlers::class, '123');

        $this->assertSame(1, $saga->actionOneCalled);
        $this->assertSame(1, $saga->actionTwoCalled);
    }
}
