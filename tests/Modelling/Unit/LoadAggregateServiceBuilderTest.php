<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateNotFoundException;
use Test\Ecotone\Messaging\BaseEcotoneTestCase;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithReturnType;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\EventSourcingHandlerMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\StaticEventSourcingHandlerMethodExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithConstructorHavingParameters;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithPrivateConstructor;
use Test\Ecotone\Modelling\Fixture\NoIdentifierAggregate\Product;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryInterface;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Saga\OrderFulfilment;
use Test\Ecotone\Modelling\Fixture\Saga\PaymentWasDoneEvent;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class LoadAggregateServiceBuilderTest extends BaseEcotoneTestCase
{
    public function test_calling_aggregate_via_query_handler()
    {
        $this->assertEquals(
            $duration = 1000,
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [Appointment::class])
                ->sendCommand(new CreateAppointmentCommand(123, $duration))
                ->sendQueryWithRouting('getDuration', metadata: ['aggregate.id' => 123])
        );
    }

    public function test_loading_aggregate_by_metadata()
    {
        $this->assertEquals(
            'done',
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [OrderFulfilment::class])
                ->sendCommandWithRoutingKey('order.start', $oderId = 100)
                ->publishEvent(PaymentWasDoneEvent::create(), metadata: ['paymentId' => $oderId])
                ->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $oderId])
        );
    }

    public function test_loading_aggregate_by_metadata_with_public_method_identifier()
    {
        $this->assertEquals(
            100,
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [PublicIdentifierGetMethodForEventSourcedAggregate::class])
                ->sendCommand(new CreateNoIdDefinedAggregate(100))
                ->getAggregate(PublicIdentifierGetMethodForEventSourcedAggregate::class, 100)
                ->getId()
        );
    }

    public function test_providing_override_aggregate_identifier_as_array()
    {
        $this->assertFalse(
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [Article::class])
                ->sendCommand(PublishArticleCommand::createWith(1000, 'Some', 'bla bla'))
                ->sendCommandWithRoutingKey('close', metadata: ['aggregate.id' => ['author' => 1000, 'title' => 'Some']])
                ->getAggregate(Article::class, ['author' => 1000, 'title' => 'Some'])
                ->isPublished()
        );
    }

    public function test_throwing_exception_if_aggregate_has_no_identifiers_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [Product::class]);
    }

    public function test_fetch_aggregate_via_business_repository()
    {
        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStandardRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createWith([$appointment]),
            ]
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);
        $this->assertEquals(
            1000,
            $repository->get(123)->getDuration()
        );
    }

    public function test_fetch_aggregate_via_business_repository_with_nulls()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStandardRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
            ]
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);
        $this->assertNull($repository->find(123));
    }

    public function test_fetch_nulls_on_non_null_business_repository()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStandardRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
            ]
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);

        $this->expectException(AggregateNotFoundException::class);
        $repository->get(123);
    }

    public function test_storing_standard_aggregate_via_business_repository(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStandardRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
            ]
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);

        $repository->save(Appointment::create(new CreateAppointmentCommand(123, 1000)));

        $this->assertEquals(
            1000,
            $repository->get(123)->getDuration()
        );
    }

    public function test_throwing_exception_if_no_id_found_in_command()
    {
        $this->expectException(AggregateNotFoundException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [AggregateWithoutMessageClassesExample::class])
            ->sendCommandWithRoutingKey('doSomething');
    }

    public function test_throwing_exception_if_no_event_sourcing_handler_defined_for_event_sourced_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [NoFactoryMethodAggregateExample::class]);
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_has_no_parameters()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [EventSourcingHandlerMethodWithWrongParameterCountExample::class]);
    }

    public function test_throwing_exception_if_construct_having_parameters()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [WithConstructorHavingParameters::class]);
    }

    public function test_throwing_exception_if_construct_is_private()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [WithPrivateConstructor::class]);
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_non_void()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [EventSourcingHandlerMethodWithReturnType::class]);
    }

    public function test_throwing_exception_if_event_sourcing_handler_is_static()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [StaticEventSourcingHandlerMethodExample::class]);
    }
}
