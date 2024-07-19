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
use Test\Ecotone\Modelling\Fixture\Retry\RetriedCommandHandler;

/**
 * @internal
 */
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

        $this->expectException(RuntimeException::class);

        $ecotoneLite->sendCommandWithRoutingKey('retried.synchronous', 2);
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
}
