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
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithReturnType;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\CreateIncorrectEventTypeReturnedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned\IncorrectEventTypeReturnedExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\StaticEventSourcingHandlerMethodExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithConstructorHavingParameters;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithPrivateConstructor;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryBuilder;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\OrderWasPaid;
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;

class LoadAggregateBuilderTest extends TestCase
{
    public function test_enriching_command_with_aggregate_if_found()
    {
        $aggregateCallingCommandHandler = LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Appointment::class)),
            "getAppointmentId",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => AppointmentStandardRepository::createWith([
                    $appointment
                ]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["appointmentId" => 123])
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $appointment,
            $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
        );
    }

    public function test_setting_correct_aggregate_version_when_loading_via_event_sourcing_repository()
    {
        $aggregateCallingCommandHandler = LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
            "assignWorker",
            ClassDefinition::createFor(TypeDescriptor::create(AssignWorkerCommand::class)),
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => InMemoryEventSourcedRepository::createWithExistingAggregate(["ticketId" => 1], Ticket::class, [new TicketWasStartedEvent(1)]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload(new AssignWorkerCommand(1, 2))
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["ticketId" => 1])
                ->setReplyChannel($replyChannel)
                ->build()
        );

        /** @var Ticket $ticket */
        $ticket = $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT);
        $this->assertEquals(
            1,
            $ticket->getVersion()
        );
    }

    public function test_enriching_command_with_aggregate_if_found_using_repository_builder()
    {
        $aggregateCallingCommandHandler = LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(Appointment::class)),
            "getAppointmentId",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"]);

        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $aggregateCommandHandler = $aggregateCallingCommandHandler->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "repository" => AppointmentRepositoryBuilder::createWith([
                    $appointment
                ]),
                ExpressionEvaluationService::REFERENCE => SymfonyExpressionEvaluationAdapter::create()
            ])
        );

        $replyChannel = QueueChannel::create();
        $aggregateCommandHandler->handle(
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ["appointmentId" => 123])
                ->setReplyChannel($replyChannel)
                ->build()
        );

        $this->assertEquals(
            $appointment,
            $replyChannel->receive()->getHeaders()->get(AggregateMessage::AGGREGATE_OBJECT)
        );
    }

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

    public function test_throwing_exception_if_no_event_sourcing_handler_defined_for_event_sourced_aggregate()
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
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingHandlerMethodWithWrongParameterCountExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_construct_having_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(WithConstructorHavingParameters::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_construct_is_private()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(WithPrivateConstructor::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_non_void()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingHandlerMethodWithReturnType::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_static()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(StaticEventSourcingHandlerMethodExample::class)),
            "doSomething",
            null,
            LoadAggregateMode::createThrowOnNotFound()
        )
            ->withAggregateRepositoryFactories(["repository"])
            ->withInputChannelName("inputChannel");
    }
}