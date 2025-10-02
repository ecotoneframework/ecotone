<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit\Config\InstantRetry;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryConfiguration;
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
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3),
                ])
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRoutingKey('retried.synchronous', 4)
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
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3),
                ])
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRoutingKey('retried.nested.sync', 4)
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
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 2),
                ])
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite->sendCommandWithRoutingKey('retried.synchronous', 4);
    }

    public function test_retrying_with_command_bus_for_concrete_exception()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3, [RuntimeException::class]),
                ])
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRoutingKey('retried.synchronous', 4)
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
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3, [InvalidArgumentException::class]),
                ])
        );

        $exceptionThrown = false;
        try {
            $ecotoneLite->sendCommandWithRoutingKey('retried.synchronous', 2);
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
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                    InstantRetryConfiguration::createWithDefaults()
                        ->withAsynchronousEndpointsRetry(true, 3),
                ])
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRoutingKey('retried.asynchronous', 4)
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
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                    InstantRetryConfiguration::createWithDefaults()
                        ->withAsynchronousEndpointsRetry(true, 3)
                        ->withCommandBusRetry(true, 3),
                ])
        );

        $this->assertEquals(
            4,
            $ecotoneLite
                ->sendCommandWithRoutingKey('retried.nested.async', 4)
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
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                    InstantRetryConfiguration::createWithDefaults()
                        ->withAsynchronousEndpointsRetry(true, 2),
                ])
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite
            ->sendCommandWithRoutingKey('retried.asynchronous', 4)
            ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());
    }

    public function test_interceptors_after_instant_retry_are_triggered_with_each_retry_for_command_bus()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [InterceptorAfterRetryHandler::class],
            [
                new InterceptorAfterRetryHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3),
                ])
        );

        // Send command that will fail 3 times before succeeding (4 total attempts)
        $ecotoneLite->sendCommandWithRoutingKey('interceptor.after.retry', 3);

        // Verify command was called 4 times
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
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                    InstantRetryConfiguration::createWithDefaults()
                        ->withAsynchronousEndpointsRetry(true, 3),
                ])
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('interceptor.after.retry.async', 3)
            ->run('async', ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());

        // Verify command was called 4 times
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
