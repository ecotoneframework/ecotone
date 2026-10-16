<?php

declare(strict_types=1);

namespace Messaging\Unit\Handler\Gateway;

use Ecotone\Api\Attribute\DelayedRetry;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\ErrorContext;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingGatewayModule;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\ChannelTestSupport;
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
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
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
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
            enabledModules: [],
        );
    }

    public function test_using_custom_error_channel_on_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, ErrorChannelCommandBus::class],
            [new TicketService()],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
            enabledModules: [],
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


        $failedMessage = ChannelTestSupport::of($ecotoneLite)->getMessageChannel('someErrorChannel')->receive();
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
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel('someErrorChannel'),
            ],
            enabledModules: [],
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


        $message = ChannelTestSupport::of($ecotoneLite)->getMessageChannel('async')->receive();
        $this->assertNotNull($message);
    }

    public function test_error_channel_on_command_handler_is_silently_ignored_must_be_placed_on_gateway(): void
    {
        // Wrong placement: #[ErrorChannel] on the handler method has no effect.
        // Must be on the messaging entry-point (CommandBus/EventBus/BusinessMethod).
        $service = new class () {
            public bool $sideEffectExecuted = false;

            #[\Ecotone\Api\Attribute\CommandHandler('handler.level.error.channel.test')]
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('handlerLevelErrorChannel')],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $thrown = null;
        try {
            $ecotoneLite->sendCommandWithRouting('handler.level.error.channel.test', 'payload');
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
            ChannelTestSupport::of($ecotoneLite)->getMessageChannel('handlerLevelErrorChannel')->receive(),
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
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel(DelayedRetry::generateGatewayChannelName(DelayedRetryCommandBus::class)),
                SimpleMessageChannelBuilder::createQueueChannel('gatewayRetryDeadLetter'),
            ],
            enabledModules: [],
        );
    }

    public function test_delayed_retry_on_command_bus_routes_failures_to_generated_channel(): void
    {
        $generatedRetryChannel = DelayedRetry::generateGatewayChannelName(DelayedRetryCommandBus::class);

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TicketService::class, DelayedRetryCommandBus::class],
            [new TicketService()],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
                SimpleMessageChannelBuilder::createQueueChannel($generatedRetryChannel),
                SimpleMessageChannelBuilder::createQueueChannel('gatewayRetryDeadLetter'),
            ],
            enabledModules: [],
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
        $retryChannel = ChannelTestSupport::of($ecotoneLite)->getMessageChannel($generatedRetryChannel);
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
    initialDelayInMilliseconds: 1,
    multiplier: 1,
    maxRetries: 1,
    deadLetterChannel: 'gatewayRetryDeadLetter',
)]
interface DelayedRetryCommandBus extends \Ecotone\Api\Gateway\CommandBus
{
}
