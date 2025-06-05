<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor;

use Ecotone\Lite\EcotoneLite;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\Gateway;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\GatewayInterceptors;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\InterceptorOrderingAggregate;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\InterceptorOrderingCase;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\InterceptorOrderingInterceptors;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\InterceptorOrderingStack;
use Test\Ecotone\Messaging\Fixture\InterceptorsOrdering\OutputHandler;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class InterceptorsOrderingTest extends TestCase
{
    public function test_command_returning_something(): void
    {
        $stack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), $stack],
        )->sendCommandWithRoutingKey('commandEndpointReturning');

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'afterChangeHeaders',
                'after',
            ],
            $stack->getCalls()
        );
    }

    public function test_command_returning_void(): void
    {
        $stack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), $stack],
        )->sendCommandWithRoutingKey('commandEndpointVoid');

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
            ],
            $stack->getCalls()
        );
    }

    public function test_service_activator_returning_something(): void
    {
        $callStack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), $callStack],
        )->sendDirectToChannel('serviceEndpointReturning');

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'afterChangeHeaders',
                'after',
            ],
            $callStack->getCalls()
        );
    }

    public function test_service_activator_returning_void(): void
    {
        $callStack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), $callStack],
        )->sendDirectToChannel('serviceEndpointVoid');

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
            ],
            $callStack->getCalls()
        );
    }

    public function test_gateway_returning_something(): void
    {
        $callStack = new InterceptorOrderingStack();
        /** @var Gateway $gateway */
        $gateway = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, Gateway::class, InterceptorOrderingInterceptors::class, GatewayInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), new GatewayInterceptors(), $callStack],
        )
            ->getGateway(Gateway::class);
        $gateway->runWithReturn();

        self::assertEquals(
            [
                'gateway::before',
                'gateway::around begin',
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'afterChangeHeaders',
                'after',
                'gateway::around end',
                'gateway::after',
            ],
            $callStack->getCalls()
        );
    }

    public function test_gateway_returning_void(): void
    {
        $callStack = new InterceptorOrderingStack();
        /** @var Gateway $gateway */
        $gateway = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, Gateway::class, InterceptorOrderingInterceptors::class, GatewayInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), new GatewayInterceptors(), $callStack],
        )
            ->getGateway(Gateway::class);
        $gateway->runWithVoid();

        self::assertEquals(
            [
                'gateway::before',
                'gateway::around begin',
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'gateway::around end',
            ],
            $callStack->getCalls()
        );
    }

    public function test_aggregate_with_factory_method(): void
    {
        $callStack = new InterceptorOrderingStack();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingAggregate::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingInterceptors(), $callStack],
        );

        $ecotone->sendCommandWithRoutingKey('endpoint', metadata: ['aggregate.id' => 'id']);

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'afterChangeHeaders',
                'after',
                'beforeChangeHeaders',
                'before',
                'around begin',
                'factory',
                'around end',
                'afterChangeHeaders',
                'after',
                'eventHandler',
            ],
            $callStack->getCalls()
        );

        $callStack->reset();
        $ecotone->sendCommandWithRoutingKey('endpoint', metadata: ['aggregate.id' => 'id']);
        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'afterChangeHeaders',
                'after',
                'beforeChangeHeaders',
                'before',
                'around begin',
                'action',
                'around end',
                'afterChangeHeaders',
                'after',
            ],
            $callStack->getCalls()
        );
    }

    public function test_aggregate_command_returning_void(): void
    {
        $callStack = new InterceptorOrderingStack();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingAggregate::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingInterceptors(), $callStack],
        )
            ->withStateFor(new InterceptorOrderingAggregate('existingAggregateId'));

        $ecotone
            ->sendCommandWithRoutingKey('actionVoid', metadata: ['aggregate.id' => 'existingAggregateId']);

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'action',
                'around end',
                'afterChangeHeaders',
                'after',
            ],
            $callStack->getCalls()
        );
    }

    public function test_command_without_output_channel(): void
    {
        $stack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, InterceptorOrderingInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), $stack],
        )->sendCommandWithRoutingKey('commandWithOutputChannel');

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'afterChangeHeaders',
                'after',

                'beforeChangeHeaders',
                'before',
                'around begin',
                'command-output-channel',
                'around end',
                'afterChangeHeaders',
                'after',
            ],
            $stack->getCalls()
        );
    }

    public function test_gateway_command_without_output_channel(): void
    {
        $callStack = new InterceptorOrderingStack();
        /** @var Gateway $gateway */
        $gateway = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingCase::class, Gateway::class, InterceptorOrderingInterceptors::class, GatewayInterceptors::class],
            [new InterceptorOrderingCase(), new InterceptorOrderingInterceptors(), new GatewayInterceptors(), $callStack],
        )
            ->getGateway(Gateway::class);
        $gateway->runWithEndpointOutputChannel();

        self::assertEquals(
            [
                'gateway::before',
                'gateway::around begin',

                'beforeChangeHeaders',
                'before',
                'around begin',
                'endpoint',
                'around end',
                'afterChangeHeaders',
                'after',

                'beforeChangeHeaders',
                'before',
                'around begin',
                'command-output-channel',
                'around end',
                'afterChangeHeaders',
                'after',

                'gateway::around end',
                'gateway::after',
            ],
            $callStack->getCalls()
        );
    }

    public function test_aggregate_command_with_output_channel(): void
    {
        $callStack = new InterceptorOrderingStack();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingAggregate::class, InterceptorOrderingInterceptors::class, OutputHandler::class],
            [new InterceptorOrderingInterceptors(), $callStack, new OutputHandler()],
        )
            ->withStateFor(new InterceptorOrderingAggregate('existingAggregateId'));
        // Remove event handler event from stack
        $callStack->reset();

        $ecotone
            ->sendCommandWithRoutingKey('endpointWithOutput', metadata: ['aggregate.id' => 'existingAggregateId']);

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'action',
                'around end',
                'afterChangeHeaders',
                'after',
                'command-output-channel',
            ],
            $callStack->getCalls()
        );
    }

    public function test_aggregate_with_factory_method_and_output_channel(): void
    {
        $callStack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingAggregate::class, InterceptorOrderingInterceptors::class, OutputHandler::class],
            [new InterceptorOrderingInterceptors(), $callStack, new OutputHandler()],
        )->sendCommandWithRoutingKey('endpointFactoryWithOutput', metadata: ['aggregate.id' => 'id']);

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'factory',
                'around end',
                'afterChangeHeaders',
                'after',

                'eventHandler',

                'command-output-channel',
            ],
            $callStack->getCalls()
        );
    }

    public function test_aggregate_with_factory_and_action_channel(): void
    {
        $callStack = new InterceptorOrderingStack();
        EcotoneLite::bootstrapFlowTesting(
            [InterceptorOrderingAggregate::class, InterceptorOrderingInterceptors::class, OutputHandler::class],
            [new InterceptorOrderingInterceptors(), $callStack, new OutputHandler()],
        )->sendCommandWithRoutingKey('endpointFactoryWithOutput', metadata: ['aggregate.id' => 'id']);

        self::assertEquals(
            [
                'beforeChangeHeaders',
                'before',
                'around begin',
                'factory',
                'around end',
                'afterChangeHeaders',
                'after',

                'eventHandler',

                'command-output-channel',
            ],
            $callStack->getCalls()
        );
    }
}
