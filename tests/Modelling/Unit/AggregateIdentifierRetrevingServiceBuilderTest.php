<?php


namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
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
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\Blog\RepublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\Handler\ReplyViaHeadersMessageHandler;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;

class AggregateIdentifierRetrevingServiceBuilderTest extends TestCase
{
    public function test_loading_aggregate_by_metadata()
    {
        $headerName                            = "paymentId";
        $aggregateCallingCommandHandler = AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
            ["orderId" => "paymentId"],
            ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class))
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

    public function test_throwing_exception_if_metadata_identifier_mapping_points_to_non_existing_aggregate_id()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(OrderFulfilment::class)),
            ["some" => "paymentId", "orderId" => "x"],
            ClassDefinition::createFor(TypeDescriptor::create(PaymentWasDoneEvent::class))
        );
    }

    public function test_throwing_exception_if_no_aggregate_identifier_definition_found()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(Article::class)),
            [],
            ClassDefinition::createFor(TypeDescriptor::create(RepublishArticleCommand::class))
        );
    }

    public function test_throwing_exception_if_no_identifiers_defined_in_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateIdentifierRetrevingServiceBuilder::createWith(
            ClassDefinition::createFor(TypeDescriptor::create(ReplyViaHeadersMessageHandler::class)),
            [],
            ClassDefinition::createFor(TypeDescriptor::create(PublishArticleCommand::class))
        );
    }
}