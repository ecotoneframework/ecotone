<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\StubLogger;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\FailureHandler\ExampleFailureCommandHandler;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class LoggingModuleTest extends TestCase
{
    public const CHANNEL_NAME = 'async';

    public function test_logging_critical_when_exception_occurred_on_message_consumer()
    {
        $loggerExample = StubLogger::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), 'logger' => $loggerExample],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(self::CHANNEL_NAME))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('handler.fail', ['command' => 2])
            ->run(self::CHANNEL_NAME, ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));

        $this->assertCount(1, $loggerExample->getCritical());
    }

    public function test_it_does_log_error_if_message_sent_to_error_channel()
    {
        $loggerExample = StubLogger::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), 'logger' => $loggerExample],
            (ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))
                ->withDefaultErrorChannel('customErrorChannel'))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(self::CHANNEL_NAME))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('customErrorChannel'))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('handler.fail', ['command' => 2]);
        $ecotoneLite->run(self::CHANNEL_NAME, ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));

        $this->assertNotNull($ecotoneLite->getMessageChannel('customErrorChannel')->receive());
        $this->assertCount(1, $loggerExample->getError());
    }
}
