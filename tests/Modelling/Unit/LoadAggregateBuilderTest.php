<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\SymfonyExpressionEvaluationAdapter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\LoadAggregateMode;
use Ecotone\Modelling\LoadAggregateServiceBuilder;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\FactoryMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\CreateIncorrectEventTypeReturnedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\IncorrectEventTypeReturnedExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NonStaticFactoryMethodExample;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;

class LoadAggregateBuilderTest extends TestCase
{
    public function test_throwing_exception_if_no_id_found_in_command()
    {
        $aggregateCallingCommandHandler = LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryStandardRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $this->expectException(AggregateNotFoundException::class);

        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_ID, [])
                ->build()
        );
    }

    public function test_throwing_exception_if_no_factory_method_defined_for_event_sourced_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        $aggregateCallingCommandHandler = LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(NoFactoryMethodAggregateExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");

        $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryEventSourcedRepository::createEmpty(),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_has_no_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(FactoryMethodWithWrongParameterCountExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_is_not_static()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(NonStaticFactoryMethodExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }
}