<?php

declare(strict_types=1);

namespace Messaging\Unit\Handler\Gateway;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\ErrorChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingGatewayModule;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
}
