<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\Config\InstantRetry;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\Ecotone\Modelling\Fixture\Retry\InterceptorAfterRetryHandler;
use Test\Ecotone\Modelling\Fixture\Retry\RetriedCommandHandler;

/**
 * licence Apache-2.0
 * @internal
 */
final class InstantRetryModuleTest extends TestCase
{
    public function test_retrying_with_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3),
            ],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRouting('retried.synchronous', 4)
                ->sendQueryWithRouting('retried.getCallCount')
        );
    }

    public function test_retrying_with_command_bus_and_ignoring_retries_on_nested_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3),
            ],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRouting('retried.nested.sync', 4)
                ->sendQueryWithRouting('retried.getCallCount')
        );
    }

    public function test_exceeding_retries_with_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 2),
            ],
            enabledModules: [],
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite->sendCommandWithRouting('retried.synchronous', 4);
    }

    public function test_retrying_with_command_bus_for_concrete_exception()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [RuntimeException::class]),
            ],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRouting('retried.synchronous', 4)
                ->sendQueryWithRouting('retried.getCallCount')
        );
    }

    public function test_retrying_with_command_bus_for_concrete_exception_when_different_thrown()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3, [InvalidArgumentException::class]),
            ],
            enabledModules: [],
        );

        $exceptionThrown = false;
        try {
            $ecotoneLite->sendCommandWithRouting('retried.synchronous', 2);
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
            $this->assertInstanceOf(RuntimeException::class, $e);
        }

        if (! $exceptionThrown) {
            $this->fail('RuntimeException was not thrown');
        }

        $this->assertEquals(1, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
    }

    public function test_retrying_with_asynchronous_handler()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 3),
            ],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRouting('retried.asynchronous', 4)
                ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup())
                ->sendQueryWithRouting('retried.getCallCount')
        );
    }

    public function test_retrying_with_asynchronous_handler_and_ignoring_retries_on_nested_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 3)->withCommandBusRetry(true, 3),
            ],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRouting('retried.nested.async', 4)
                ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup())
                ->sendQueryWithRouting('retried.getCallCount')
        );
    }

    public function test_exceeding_retries_with_asynchronous_handler()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 2),
            ],
            enabledModules: [],
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite
            ->sendCommandWithRouting('retried.asynchronous', 4)
            ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());
    }

    public function test_interceptors_after_instant_retry_are_triggered_with_each_retry_for_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InterceptorAfterRetryHandler::class],
            [
                new InterceptorAfterRetryHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withCommandBusRetry(true, 3),
            ],
            enabledModules: [],
        );

        $ecotoneLite->sendCommandWithRouting('interceptor.after.retry', 3);

        $this->assertEquals(
            [
                'preRetryInterceptor',
                'interceptor',
                'commandHandler',
                'interceptor',
                'commandHandler',
                'interceptor',
                'commandHandler',
                'interceptor',
                'commandHandler',
            ],
            $ecotoneLite->sendQueryWithRouting('interceptor.getCalls')
        );
    }

    public function test_interceptors_after_instant_retry_are_triggered_with_each_retry_for_asynchronous_handlers()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InterceptorAfterRetryHandler::class],
            [
                new InterceptorAfterRetryHandler(),
            ],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(true, 3),
            ],
            enabledModules: [],
        );

        $ecotoneLite
            ->sendCommandWithRouting('interceptor.after.retry.async', 3)
            ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());

        $this->assertEquals(
            [
                'preRetryInterceptor',
                'interceptor',
                'asyncPreRetryInterceptor',
                'asyncInterceptor',
                'asyncCommandHandler',
                'asyncInterceptor',
                'asyncCommandHandler',
                'asyncInterceptor',
                'asyncCommandHandler',
                'asyncInterceptor',
                'asyncCommandHandler',
            ],
            $ecotoneLite->sendQueryWithRouting('interceptor.getCalls')
        );
    }
}
