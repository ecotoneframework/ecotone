<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Test\LoggerExample;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\FailureHandler\ExampleFailureCommandHandler;

/**
 * @internal
 */
final class LoggingModuleTest extends TestCase
{
    public const CHANNEL_NAME = 'async';

    public function test_logging_critical_when_exception_occurred_on_message_consumer()
    {
        $loggerExample = LoggerExample::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), LoggingHandlerBuilder::LOGGER_REFERENCE => $loggerExample],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(self::CHANNEL_NAME),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('handler.fail', ['command' => 2])
            ->run(self::CHANNEL_NAME, ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertCount(1, $loggerExample->getCritical());
    }

    public function test_it_does_not_log_critical_if_message_sent_to_error_channel()
    {
        $loggerExample = LoggerExample::create();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), LoggingHandlerBuilder::LOGGER_REFERENCE => $loggerExample],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultErrorChannel('customErrorChannel'),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(self::CHANNEL_NAME),
                SimpleMessageChannelBuilder::createQueueChannel('customErrorChannel'),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('handler.fail', ['command' => 2]);
        $ecotoneLite->run(self::CHANNEL_NAME, ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertNotNull($ecotoneLite->getMessageChannel('customErrorChannel')->receive());
        $this->assertCount(0, $loggerExample->getCritical());
    }
}
