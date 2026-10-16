<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Test\ChannelTestSupport;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ServiceConfigurationFromServiceContextTest extends TestCase
{
    public function test_default_error_channel_returned_from_service_context_receives_the_failed_message(): void
    {
        $serviceContext = new class () {
            #[ServiceContext]
            public function serviceConfiguration(): ServiceConfiguration
            {
                return ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('customErrorChannel');
            }
        };
        $handler = new class () {
            #[Asynchronous('async')]
            #[CommandHandler('handler.fail', endpointId: 'failing')]
            public function fail(): void
            {
                throw new InvalidArgumentException('failing');
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$serviceContext::class, $handler::class],
            [$serviceContext, $handler],
            extensionObjects: [
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('customErrorChannel'),
            ],
            enabledModules: [],
        );

        $ecotone
            ->sendCommandWithRouting('handler.fail')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));

        $this->assertNotNull(ChannelTestSupport::of($ecotone)->getMessageChannel('customErrorChannel')->receive());
    }

    public function test_two_service_context_methods_returning_service_configuration_are_refused_at_bootstrap(): void
    {
        $first = new class () {
            #[ServiceContext]
            public function first(): ServiceConfiguration
            {
                return ServiceConfiguration::createWithDefaults()->withServiceName('first');
            }
        };
        $second = new class () {
            #[ServiceContext]
            public function second(): ServiceConfiguration
            {
                return ServiceConfiguration::createWithDefaults()->withServiceName('second');
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Ecotone was given more than one ServiceConfiguration: ' . $first::class . '::first() and ' . $second::class . '::second() each return one. '
            . 'Ecotone reads exactly one ServiceConfiguration, so it cannot tell which serviceName, defaultSerializationMediaType, defaultErrorChannel, defaultMemoryLimit, connectionRetryTemplate or headersInLogContext to apply. '
            . 'Keep one #[ServiceContext] method returning ServiceConfiguration::createWithDefaults() and move every with*() call onto it.'
        );

        EcotoneLite::bootstrapFlowTesting(
            [$first::class, $second::class],
            [$first, $second],
            enabledModules: [],
        );
    }

    public function test_service_configuration_passed_to_ecotone_lite_and_returned_from_service_context_is_refused_at_bootstrap(): void
    {
        $serviceContext = new class () {
            #[ServiceContext]
            public function serviceConfiguration(): ServiceConfiguration
            {
                return ServiceConfiguration::createWithDefaults()->withServiceName('from_service_context');
            }
        };

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Ecotone was given more than one ServiceConfiguration: one in the extensionObjects passed to EcotoneLite and one returned from ' . $serviceContext::class . '::serviceConfiguration(). '
            . 'Ecotone reads exactly one ServiceConfiguration, so it cannot tell which serviceName, defaultSerializationMediaType, defaultErrorChannel, defaultMemoryLimit, connectionRetryTemplate or headersInLogContext to apply. '
            . 'Keep one of them and move every with*() call onto it.'
        );

        EcotoneLite::bootstrapFlowTesting(
            [$serviceContext::class],
            [$serviceContext],
            extensionObjects: [ServiceConfiguration::createWithDefaults()->withServiceName('from_lite')],
            enabledModules: [],
        );
    }
}
