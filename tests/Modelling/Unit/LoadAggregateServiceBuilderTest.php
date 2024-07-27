<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateMode;
use Ecotone\Modelling\AggregateFlow\LoadAggregate\LoadAggregateServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\SnapshotEvent;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\BaseEcotoneTest;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithReturnType;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\StaticEventSourcingHandlerMethodExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithConstructorHavingParameters;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithPrivateConstructor;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryBuilder;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\AssignWorkerCommand;
use Test\Ecotone\Modelling\Fixture\Ticket\Ticket;
use Test\Ecotone\Modelling\Fixture\Ticket\TicketWasStartedEvent;
use Test\Ecotone\Modelling\Fixture\Ticket\WorkerWasAssignedEvent;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class LoadAggregateServiceBuilderTest extends BaseEcotoneTest
{
    public function test_enriching_command_with_aggregate_if_found()
    {
        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', AppointmentRepositoryBuilder::createWith([
                $appointment,
            ]))
            ->withMessageHandler(
                LoadAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Appointment::class)),
                    'getAppointmentId',
                    null,
                    LoadAggregateMode::createThrowOnNotFound(),
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['appointmentId' => 123])
                ->build()
        );

        $this->assertEquals(
            $appointment,
            $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT)
        );
    }

    public function test_setting_correct_aggregate_version_when_loading_via_event_sourcing_repository()
    {
        $ticketWasStartedEvent = new TicketWasStartedEvent(1);
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createWithExistingAggregate(['ticketId' => 1], Ticket::class, [$ticketWasStartedEvent]))
            ->withMessageHandler(
                LoadAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'assignWorker',
                    ClassDefinition::createFor(TypeDescriptor::create(AssignWorkerCommand::class)),
                    LoadAggregateMode::createThrowOnNotFound(),
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(new AssignWorkerCommand(1, 2))
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['ticketId' => 1])
                ->build()
        );

        $ticket = new Ticket();
        $ticket->onTicketWasStarted($ticketWasStartedEvent);
        $ticket->setVersion(1);

        /** @var Ticket $reconstructedTicket */
        $reconstructedTicket = $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
        $this->assertEquals(
            $ticket,
            $reconstructedTicket
        );
    }

    /**
     * @dataProvider enterpriseMode
     */
    public function test_setting_correct_aggregate_when_snapshot_is_used(bool $isEnterpriseMode)
    {
        $ticket = new Ticket();
        $ticket->onTicketWasStarted(new TicketWasStartedEvent(1));
        $extraEvent = new WorkerWasAssignedEvent(1, 100);

        $messaging = ComponentTestBuilder::create(defaultEnterpriseMode: $isEnterpriseMode)
            ->withReference('repository', InMemoryEventSourcedRepository::createWithExistingAggregate(['ticketId' => 1], Ticket::class, [new SnapshotEvent(clone $ticket), $extraEvent]))
            ->withMessageHandler(
                LoadAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Ticket::class)),
                    'assignWorker',
                    ClassDefinition::createFor(TypeDescriptor::create(AssignWorkerCommand::class)),
                    LoadAggregateMode::createThrowOnNotFound(),
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $messaging->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload(new AssignWorkerCommand(1, 2))
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['ticketId' => 1])
                ->build()
        );

        $ticket->onWorkerWasAssigned($extraEvent);
        $ticket->setVersion(2);

        /** @var Ticket $ticket */
        $reconstructedTicket = $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
        $this->assertEquals(
            $ticket,
            $reconstructedTicket
        );
    }

    public function test_enriching_command_with_aggregate_if_found_using_repository_builder()
    {
        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $aggregateCommandHandler = ComponentTestBuilder::create()
            ->withReference('repository', AppointmentRepositoryBuilder::createWith([
                $appointment,
            ]))
            ->withMessageHandler(
                LoadAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(Appointment::class)),
                    'getAppointmentId',
                    null,
                    LoadAggregateMode::createThrowOnNotFound(),
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $replyMessage = $aggregateCommandHandler->sendDirectToChannelWithMessageReply(
            $inputChannel,
            MessageBuilder::withPayload([])
                ->setHeader(AggregateMessage::AGGREGATE_ID, ['appointmentId' => 123])
                ->build()
        );

        $this->assertEquals(
            $appointment,
            $replyMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_OBJECT)
        );
    }

    public function test_throwing_exception_if_no_id_found_in_command()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference('repository', AppointmentStandardRepository::createEmpty())
            ->withMessageHandler(
                LoadAggregateServiceBuilder::create(
                    ClassDefinition::createFor(TypeDescriptor::create(AggregateWithoutMessageClassesExample::class)),
                    'doSomething',
                    null,
                    LoadAggregateMode::createThrowOnNotFound(),
                    InterfaceToCallRegistry::createEmpty()
                )
                    ->withAggregateRepositoryFactories(['repository'])
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->expectException(AggregateNotFoundException::class);

        $messaging->sendMessageDirectToChannel(
            $inputChannel,
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
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');

        ComponentTestBuilder::create()
            ->withReference('repository', InMemoryEventSourcedRepository::createEmpty())
            ->withMessageHandler(
                $aggregateCallingCommandHandler
            )
            ->build();
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_has_no_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingHandlerMethodWithWrongParameterCountExample::class)),
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');
    }

    public function test_throwing_exception_if_construct_having_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(WithConstructorHavingParameters::class)),
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');
    }

    public function test_throwing_exception_if_construct_is_private()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(WithPrivateConstructor::class)),
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_non_void()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(EventSourcingHandlerMethodWithReturnType::class)),
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_static()
    {
        $this->expectException(InvalidArgumentException::class);

        LoadAggregateServiceBuilder::create(
            ClassDefinition::createFor(TypeDescriptor::create(StaticEventSourcingHandlerMethodExample::class)),
            'doSomething',
            null,
            LoadAggregateMode::createThrowOnNotFound(),
            InterfaceToCallRegistry::createEmpty()
        )
            ->withAggregateRepositoryFactories(['repository'])
            ->withInputChannelName('inputChannel');
    }
}
