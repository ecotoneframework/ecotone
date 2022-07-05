<?php


namespace Ecotone\Tests\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\LoadAggregateMode;
use Ecotone\Modelling\LoadAggregateServiceBuilder;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use PHPUnit\Framework\TestCase;
use Ecotone\Tests\Modelling\Fixture\Blog\Article;
use Ecotone\Tests\Modelling\Fixture\Blog\ChangeArticleContentCommand;
use Ecotone\Tests\Modelling\Fixture\Blog\PublishArticleCommand;
use Ecotone\Tests\Modelling\Fixture\Blog\RepublishArticleCommand;
use Ecotone\Tests\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Ecotone\Tests\Modelling\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Ecotone\Tests\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Ecotone\Tests\Modelling\Fixture\InterceptingAggregate\Basket;
use Ecotone\Tests\Modelling\Fixture\NoIdentifierAggregate\Product;
use Ecotone\Tests\Modelling\Fixture\Saga\OrderFulfilment;
use Ecotone\Tests\Modelling\Fixture\Saga\PaymentWasDoneEvent;

class AggregateIdentifierRetrevingServiceBuilderTest extends TestCase
{
    public function test_loading_aggregate_by_metadata()
    {
        $headerName                            = "paymentId";
        $aggregateCallingCommandHandler = AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
            ["orderId" => "paymentId"],
            ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class)),
            InterfaceToCallRegistry::createEmpty()
        );

        $orderId                 = 1000;
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $command = PaymentWasDoneEvent::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($command)
                ->setHeader($headerName, $orderId)
                ->setReplyChannel($replyChannel)->build()
        );

        $this->assertEquals(["orderId" => $orderId], $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_loading_aggregate_by_metadata_with_public_method_identifier()
    {
        $headerName                            = "orderId";
        $aggregateRetrevingServiceHandler = AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(PublicIdentifierGetMethodForEventSourcedAggregate::class)),
            ["id" => "orderId"],
            null,
            InterfaceToCallRegistry::createEmpty()
        );

        $orderId                 = 1000;
        $aggregateRetrievingService = $aggregateRetrevingServiceHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $command = PaymentWasDoneEvent::create();
        $aggregateRetrievingService->handle(
            MessageBuilder::withPayload($command)
                ->setHeader($headerName, $orderId)
                ->setReplyChannel($replyChannel)->build()
        );

        $this->assertEquals(["id" => $orderId], $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_providing_override_aggregate_identifier()
    {
        $aggregateCallingCommandHandler = AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(Basket::class)),
            [],
            null,
            InterfaceToCallRegistry::createEmpty()
        );

        $orderId                 = 1000;
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $command = PaymentWasDoneEvent::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($command)
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, $orderId)
                ->setReplyChannel($replyChannel)->build()
        );

        $this->assertEquals(["userId" => $orderId], $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_providing_override_aggregate_identifier_as_array()
    {
        $aggregateCallingCommandHandler = AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
            [],
            ClassDefinition::createFor(TypeDescriptor::create(ChangeArticleContentCommand::class)),
            InterfaceToCallRegistry::createEmpty()
        );

        $aggregateIds                 = ["author" => 1000, "title" => "Some"];
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $command = PaymentWasDoneEvent::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload($command)
                ->setHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER, $aggregateIds)
                ->setReplyChannel($replyChannel)->build()
        );

        $this->assertEquals($aggregateIds, $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_ID));
    }

    public function test_throwing_exception_if_aggregate_has_no_identifiers_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(Product::class)),
            [],
            null,
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_throwing_exception_if_metadata_identifier_mapping_points_to_non_existing_aggregate_id()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
            ["some" => "paymentId", "orderId" => "x"],
            ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class)),
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_throwing_exception_if_no_aggregate_identifier_definition_found()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
            [],
            ClassDefinition::createFor(TypeDescriptor::create(RepublishArticleCommand::class)),
            InterfaceToCallRegistry::createEmpty()
        );
    }
}