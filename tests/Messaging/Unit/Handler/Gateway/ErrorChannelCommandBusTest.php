<?php

declare(strict_types=1);

namespace Messaging\Unit\Handler\Gateway;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\DelayedRetry;
use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingGatewayModule;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\ErrorChannelCommandBus;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\ErrorChannelWithAsyncChannel;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\TicketService;
use Test\Ecotone\Messaging\SerializationSupport;

/**
 * licence Enterprise
 * @internal
 */
#[CoversClass(ErrorChannel::class)]
#[CoversClass(MessagingGatewayModule::class)]
final class ErrorChannelCommandBusTest extends TestCase
{
    public function test_it_throws_when_using_in_non_enterprise_mode(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, ErrorChannelCommandBus::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
        );
    }

    public function test_using_custom_error_channel_on_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, ErrorChannelCommandBus::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $commandBus = $ecotoneLite->getGateway(ErrorChannelCommandBus::class);

        $payload = Uuid::uuid4();
        $commandBus->sendWithRouting(
            'createViaCommand',
            $payload,
            metadata: [
                'throwException' => true,
            ]
        );

        $this->assertEquals(
            [],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );


        $failedMessage = $ecotoneLite->getMessageChannel('someErrorChannel')->receive();
        $messagingException = $failedMessage->getHeaders()->get(ErrorContext::EXCEPTION_MESSAGE);
        $this->assertSame('test', $messagingException);
        /** It should be converted to serializable payload */
        $this->assertSame(MediaType::createApplicationXPHPSerialized(), $failedMessage->getHeaders()->getContentType());
        $typeId = $failedMessage->getHeaders()->get(MessageHeaders::TYPE_ID);
        $this->assertTrue(is_subclass_of($typeId, UuidInterface::class));
        $this->assertSame(SerializationSupport::withPHPSerialization($payload), $failedMessage->getPayload());
    }

    public function test_using_custom_error_channel_with_reply_channel(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, ErrorChannelWithAsyncChannel::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $commandBus = $ecotoneLite->getGateway(ErrorChannelWithAsyncChannel::class);

        $payload = Uuid::uuid4();
        $commandBus->sendWithRouting(
            'createViaCommand',
            $payload,
            metadata: [
                'throwException' => true,
            ]
        );

        $this->assertEquals(
            [],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );


        $message = $ecotoneLite->getMessageChannel('async')->receive();
        $this->assertNotNull($message);
    }

    public function test_error_channel_on_command_handler_is_silently_ignored_must_be_placed_on_gateway(): void
    {
        // Wrong placement: #[ErrorChannel] on the handler method has no effect.
        // Must be on the messaging entry-point (CommandBus/EventBus/BusinessMethod).
        $service = new class () {
            public bool $sideEffectExecuted = false;

            #[\Ecotone\Modelling\Attribute\CommandHandler('handler.level.error.channel.test')]
            #[ErrorChannel('handlerLevelErrorChannel')]
            public function handle(mixed $payload): void
            {
                $this->sideEffectExecuted = true;
                throw new RuntimeException('handler-failure');
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('handlerLevelErrorChannel'),
                ]),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $thrown = null;
        try {
            $ecotoneLite->sendCommandWithRoutingKey('handler.level.error.channel.test', 'payload');
        } catch (RuntimeException $exception) {
            $thrown = $exception;
        }

        $this->assertNotNull(
            $thrown,
            'Exception must propagate to the bus caller — #[ErrorChannel] on a #[CommandHandler] is not wired by any resolver.'
        );
        $this->assertSame('handler-failure', $thrown->getMessage());
        $this->assertTrue(
            $service->sideEffectExecuted,
            'Handler ran and produced side effects with no rollback boundary in place.'
        );
        $this->assertNull(
            $ecotoneLite->getMessageChannel('handlerLevelErrorChannel')->receive(),
            '#[ErrorChannel] on the handler is silently ignored — no message routed. '
            . 'Place it on the gateway entry-point (CommandBus/EventBus/BusinessMethod) so the framework can capture failures '
            . 'after gateway-level interceptors (e.g. transactional rollback) have fully unwound.'
        );
    }

    public function test_delayed_retry_on_command_bus_throws_in_non_enterprise_mode(): void
    {
        $this->expectException(LicensingException::class);
        $this->expectExceptionMessage('#[DelayedRetry]');

        EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, DelayedRetryCommandBus::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel(DelayedRetry::generateGatewayChannelName(DelayedRetryCommandBus::class)),
                SimpleMessageChannelBuilder::createQueueChannel('gatewayRetryDeadLetter'),
            ],
        );
    }

    public function test_delayed_retry_on_command_bus_routes_failures_to_generated_channel(): void
    {
        $generatedRetryChannel = DelayedRetry::generateGatewayChannelName(DelayedRetryCommandBus::class);

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, DelayedRetryCommandBus::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel($generatedRetryChannel),
                SimpleMessageChannelBuilder::createQueueChannel('gatewayRetryDeadLetter'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $commandBus = $ecotoneLite->getGateway(DelayedRetryCommandBus::class);
        $payload = Uuid::uuid4();
        $commandBus->sendWithRouting(
            'createViaCommand',
            $payload,
            metadata: ['throwException' => true],
        );

        $this->assertEquals(
            [],
            $ecotoneLite->sendQueryWithRouting('getTickets'),
            'Handler must throw, leaving no ticket created'
        );

        /** @var PollableChannel $retryChannel */
        $retryChannel = $ecotoneLite->getMessageChannel($generatedRetryChannel);
        $this->assertNotNull(
            $retryChannel->receive(),
            "#[DelayedRetry] on a CommandBus gateway must route failures to the auto-generated channel `{$generatedRetryChannel}`"
        );
    }
}

/**
 * Custom Command Bus declaring a per-gateway #[DelayedRetry] policy.
 *
 * @internal
 */
#[DelayedRetry(
    initialDelayMs: 1,
    multiplier: 1,
    maxAttempts: 1,
    deadLetterChannel: 'gatewayRetryDeadLetter',
)]
interface DelayedRetryCommandBus extends \Ecotone\Modelling\CommandBus
{
}
