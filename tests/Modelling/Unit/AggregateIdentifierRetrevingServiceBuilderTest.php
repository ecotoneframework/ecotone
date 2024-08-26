<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Test\ComponentTestBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\ChangeArticleContentCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Test\Ecotone\Modelling\Fixture\InterceptingAggregate\Basket;
use Test\Ecotone\Modelling\Fixture\NoIdentifierAggregate\Product;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class AggregateIdentifierRetrevingServiceBuilderTest extends TestCase
{
    public function test_loading_aggregate_by_metadata()
    {
        $headerName                            = 'paymentId';
        $orderId                 = 1000;

        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chain(
                        AggregateIdentifierRetrevingServiceBuilder::createWith(
                            ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
                            ['orderId' => $headerName],
                            [],
                            ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class)),
                            InterfaceToCallRegistry::createEmpty()
                        )
                    )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(PaymentWasDoneEvent::create())
                ->setHeader($headerName, $orderId)
                ->build()
        );

        $this->assertEquals(['orderId' => $orderId], $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_loading_aggregate_by_metadata_with_public_method_identifier()
    {
        $headerName                            = 'orderId';
        $orderId                 = 1000;
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chain(
                        AggregateIdentifierRetrevingServiceBuilder::createWith(
                            ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodForEventSourcedAggregate::class)),
                            ['id' => 'orderId'],
                            [],
                            null,
                            InterfaceToCallRegistry::createEmpty()
                        )
                    )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(PaymentWasDoneEvent::create())
                ->setHeader($headerName, $orderId)
                ->build()
        );

        $this->assertEquals(['id' => $orderId], $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_providing_override_aggregate_identifier()
    {
        $orderId                 = 1000;
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chain(
                        AggregateIdentifierRetrevingServiceBuilder::createWith(
                            ClassDefinition::createFor(TypeDescriptor::create(Basket::class)),
                            [],
                            [],
                            null,
                            InterfaceToCallRegistry::createEmpty()
                        )
                    )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(PaymentWasDoneEvent::create())
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, $orderId)
                ->build()
        );

        $this->assertEquals(['userId' => $orderId], $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_providing_override_aggregate_identifier_as_array()
    {
        $aggregateIds                 = ['author' => 1000, 'title' => 'Some'];
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryStandardRepository::createEmpty())
            ->withMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->chain(
                        AggregateIdentifierRetrevingServiceBuilder::createWith(
                            ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
                            [],
                            [],
                            ClassDefinition::createFor(TypeDescriptor::create(ChangeArticleContentCommand::class)),
                            InterfaceToCallRegistry::createEmpty()
                        )
                    )
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $message = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(PaymentWasDoneEvent::create())
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, $aggregateIds)
                ->build()
        );

        $this->assertEquals($aggregateIds, $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_throwing_exception_if_aggregate_has_no_identifiers_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                AggregateIdentifierRetrevingServiceBuilder::createWith(
                    ClassDefinition::createFor(TypeDescriptor::create(Product::class)),
                    [],
                    [],
                    null,
                    InterfaceToCallRegistry::createEmpty()
                )
            )
            ->build()
        ;
    }

    public function test_throwing_exception_if_metadata_identifier_mapping_points_to_non_existing_aggregate_id()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                AggregateIdentifierRetrevingServiceBuilder::createWith(
                    ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
                    ['some' => 'paymentId', 'orderId' => 'x'],
                    [],
                    ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class)),
                    InterfaceToCallRegistry::createEmpty()
                )
            )
            ->build();
    }
}
