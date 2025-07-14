<?php

declare(strict_types=1);

namespace Modelling\Unit\Config\InstantRetry;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\Attribute\InstantRetry;
use Ecotone\Modelling\Config\InstantRetry\InstantRetryConfiguration;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Test\Ecotone\Modelling\Fixture\Retry\CommandBusWithCustomRetryCountAttribute;
use Test\Ecotone\Modelling\Fixture\Retry\CommandBusWithErrorChannelAndInstantRetryAttribute;
use Test\Ecotone\Modelling\Fixture\Retry\CommandBusWithInvalidArgumentExceptionsAttribute;
use Test\Ecotone\Modelling\Fixture\Retry\CommandBusWithRuntimeExceptionsAttribute;
use Test\Ecotone\Modelling\Fixture\Retry\ErrorChannelHandler;
use Test\Ecotone\Modelling\Fixture\Retry\NonCommandBusWithInstantRetryAttribute;
use Test\Ecotone\Modelling\Fixture\Retry\RetriedCommandHandler;

/**
 * licence Enterprise
 * @internal
 */
#[CoversClass(InstantRetry::class)]
final class InstantRetryAttributeModuleTest extends TestCase
{
    public function test_throwing_exception_if_no_licence_for_instant_retry_attribute()
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithCustomRetryCountAttribute::class],
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
                ]),
        );
    }

    public function test_exceeding_retries_with_command_bus_using_attribute()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithCustomRetryCountAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults(),
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite
            ->getGateway(CommandBusWithCustomRetryCountAttribute::class)
            ->sendWithRouting('retried.synchronous', 4);
    }

    public function test_retrying_with_command_bus_using_attribute_for_specific_exceptions_which_is_not_thrown()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithInvalidArgumentExceptionsAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults(),
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $exceptionThrown = false;
        try {
            $commandBus = $ecotoneLite->getGateway(CommandBusWithInvalidArgumentExceptionsAttribute::class);
            $commandBus->sendWithRouting('retried.synchronous', 2);
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
        }

        if (! $exceptionThrown) {
            $this->fail('RuntimeException was not thrown');
        }

        $this->assertEquals(1, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
    }

    public function test_retrying_with_command_bus_using_attribute_for_specific_exceptions_which_is_thrown()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithRuntimeExceptionsAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults(),
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $commandBus = $ecotoneLite->getGateway(CommandBusWithRuntimeExceptionsAttribute::class);
        $commandBus->sendWithRouting('retried.synchronous', 2);

        $this->assertEquals(2, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
    }

    public function test_attribute_configuration_takes_precedence_over_global_configuration_with_success()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithCustomRetryCountAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $ecotoneLite
            ->getGateway(CommandBusWithCustomRetryCountAttribute::class)
            ->sendWithRouting('retried.synchronous', 3);

        $this->assertEquals(3, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
    }

    public function test_attribute_configuration_takes_precedence_over_global_configuration_with_failure()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithCustomRetryCountAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    InstantRetryConfiguration::createWithDefaults()
                        ->withCommandBusRetry(true, 3),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $this->expectException(RuntimeException::class);

        $ecotoneLite
            ->getGateway(CommandBusWithCustomRetryCountAttribute::class)
            ->sendWithRouting('retried.synchronous', 4);
    }

    public function test_instant_retry_recovers_before_error_channel()
    {
        $errorChannelHandler = new ErrorChannelHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithErrorChannelAndInstantRetryAttribute::class, ErrorChannelHandler::class],
            [
                new RetriedCommandHandler(),
                $errorChannelHandler,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $commandBus = $ecotoneLite->getGateway(CommandBusWithErrorChannelAndInstantRetryAttribute::class);
        $commandBus->sendWithRouting('retried.synchronous', 3);

        $this->assertEquals(3, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
        $this->assertFalse($errorChannelHandler->wasErrorHandled());
    }

    public function test_error_channel_used_when_instant_retries_exceeded()
    {
        $errorChannelHandler = new ErrorChannelHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, CommandBusWithErrorChannelAndInstantRetryAttribute::class, ErrorChannelHandler::class],
            [
                new RetriedCommandHandler(),
                $errorChannelHandler,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE
        );

        $commandBus = $ecotoneLite->getGateway(CommandBusWithErrorChannelAndInstantRetryAttribute::class);

        $commandBus->sendWithRouting('retried.synchronous', 4);

        $this->assertEquals(3, $ecotoneLite->sendQueryWithRouting('retried.getCallCount'));
        $this->assertTrue($errorChannelHandler->wasErrorHandled());
    }

    public function test_instant_retry_attribute_fails_on_non_command_bus_interfaces()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('InstantRetry attribute can only be used on interfaces extending CommandBus');

        EcotoneLite::bootstrapFlowTesting(
            [RetriedCommandHandler::class, NonCommandBusWithInstantRetryAttribute::class],
            [
                new RetriedCommandHandler(),
            ],
            ServiceConfiguration::createWithDefaults(),
            licenceKey: LicenceTesting::VALID_LICENCE
        );
    }
}
