<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Endpoint\ContentType;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\CommandHandler;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\HeaderConversion\JsonConverter;

/**
 * licence Apache-2.0
 * @internal
 */
final class ContentTypeAttributeTest extends TestCase
{
    public function test_using_provided_content_type_when_message_has_none(): void
    {
        $orderService = new class () {
            public ?array $order = null;

            #[ContentType('application/json')]
            #[Asynchronous('async')]
            #[CommandHandler('order.place', endpointId: 'orderPlaceEndpoint')]
            public function place(array $order): void
            {
                $this->order = $order;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [get_class($orderService), JsonConverter::class],
            [$orderService, new JsonConverter()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );

        $ecotoneLite->sendMessageDirectToChannel(
            'order.place',
            MessageBuilder::withPayload('{"product":"Book"}')->build(),
        );
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(['product' => 'Book'], $orderService->order);
    }

    public function test_keeping_content_type_from_message_when_already_defined(): void
    {
        $orderService = new class () {
            public ?array $order = null;

            #[ContentType('application/json')]
            #[Asynchronous('async')]
            #[CommandHandler('order.place', endpointId: 'orderPlaceEndpoint')]
            public function place(array $order): void
            {
                $this->order = $order;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [get_class($orderService), JsonConverter::class],
            [$orderService, new JsonConverter()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );

        $ecotoneLite->sendMessageDirectToChannel(
            'order.place',
            MessageBuilder::withPayload(serialize(['product' => 'Chair']))
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build(),
        );
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(['product' => 'Chair'], $orderService->order);
    }

    public function test_replacing_content_type_from_message_when_marked_to_replace_existing_one(): void
    {
        $orderService = new class () {
            public ?array $order = null;

            #[ContentType('application/json', replaceIfExists: true)]
            #[Asynchronous('async')]
            #[CommandHandler('order.place', endpointId: 'orderPlaceEndpoint')]
            public function place(array $order): void
            {
                $this->order = $order;
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [get_class($orderService), JsonConverter::class],
            [$orderService, new JsonConverter()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );

        $ecotoneLite->sendMessageDirectToChannel(
            'order.place',
            MessageBuilder::withPayload('{"product":"Table"}')
                ->setContentType(MediaType::createApplicationXPHPSerialized())
                ->build(),
        );
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(['product' => 'Table'], $orderService->order);
    }
}
