<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Exception;
use Test\Ecotone\Messaging\BaseEcotoneTestCase;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateWithoutMessageClassesExample;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\ApplyMethodWithReturnType;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\ApplyMethodWithWrongParameterCountExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoFactoryMethodAggregateExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\PublicIdentifierGetMethodForEventSourcedAggregate;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\StaticApplyMethodExample;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithConstructorHavingParameters;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\WithPrivateConstructor;
use Test\Ecotone\Modelling\Fixture\NoIdentifierAggregate\Product;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryInterface;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStateStoredRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Saga\AsynchronousOrderFulfilment;
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
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [Appointment::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]))
                ->sendCommand(new CreateAppointmentCommand(123, $duration))
                ->sendQueryWithRouting('getDuration', metadata: ['aggregate.id' => 123])
        );
    }

    public function test_loading_aggregate_by_metadata()
    {
        $this->assertEquals(
            'done',
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [OrderFulfilment::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]))
                ->sendCommandWithRouting('order.start', $oderId = 100)
                ->publishEvent(PaymentWasDoneEvent::create($oderId), metadata: ['paymentId' => $oderId])
                ->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $oderId])
        );
    }

    public function test_loading_aggregate_by_metadata_async_scenario()
    {
        $this->assertEquals(
            'done',
            EcotoneLite::bootstrapFlowTesting(
                classesToResolve: [AsynchronousOrderFulfilment::class],
                configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
                    ->withEnabledModulePackageNames([])
            )
                ->sendCommandWithRouting('order.start', $oderId = 100)
                ->publishEvent(PaymentWasDoneEvent::create($oderId), metadata: ['paymentId' => $oderId])
                ->run('async')
                ->sendQueryWithRouting('order.status', metadata: ['aggregate.id' => $oderId])
        );
    }

    public function test_loading_aggregate_by_metadata_with_public_method_identifier()
    {
        $this->assertEquals(
            100,
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [PublicIdentifierGetMethodForEventSourcedAggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]))
                ->sendCommand(new CreateNoIdDefinedAggregate(100))
                ->getAggregate(PublicIdentifierGetMethodForEventSourcedAggregate::class, 100)
                ->getId()
        );
    }

    public function test_providing_override_aggregate_identifier_as_array()
    {
        $this->assertFalse(
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [Article::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]))
                ->sendCommand(PublishArticleCommand::createWith(1000, 'Some', 'bla bla'))
                ->sendCommandWithRouting('close', metadata: ['aggregate.id' => ['author' => 1000, 'title' => 'Some']])
                ->getAggregate(Article::class, ['author' => 1000, 'title' => 'Some'])
                ->isPublished()
        );
    }

    public function test_throwing_exception_if_aggregate_has_no_identifiers_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [Product::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_fetch_aggregate_via_business_repository()
    {
        $appointment = Appointment::create(new CreateAppointmentCommand(123, 1000));
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStateStoredRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createWith([$appointment]),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
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
            [Appointment::class, AppointmentStateStoredRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createEmpty(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);
        $this->assertNull($repository->find(123));
    }

    public function test_fetch_nulls_on_non_null_business_repository()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStateStoredRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createEmpty(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        /** @var AppointmentRepositoryInterface $repository */
        $repository = $ecotoneLite->getServiceFromContainer(AppointmentRepositoryInterface::class);

        $this->expectException(AggregateNotFoundException::class);
        $repository->get(123);
    }

    public function test_storing_standard_aggregate_via_business_repository(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [Appointment::class, AppointmentStateStoredRepository::class, AppointmentRepositoryInterface::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createEmpty(),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
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

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [AggregateWithoutMessageClassesExample::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]))
            ->sendCommandWithRouting('doSomething');
    }

    public function test_throwing_exception_if_no_apply_method_defined_for_event_sourced_aggregate()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("You must define at least one #[Apply] method to provide aggregate's identifier after first event.");

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [NoFactoryMethodAggregateExample::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_throwing_exception_if_factory_method_for_event_sourced_aggregate_has_no_parameters()
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(ApplyMethodWithWrongParameterCountExample::class .'::factory is an #[Apply] method and should have at least one parameter.');

        try {
            EcotoneLite::bootstrapFlowTesting(classesToResolve: [ApplyMethodWithWrongParameterCountExample::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function test_throwing_exception_if_construct_having_parameters()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [WithConstructorHavingParameters::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_throwing_exception_if_construct_is_private()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [WithPrivateConstructor::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_throwing_exception_if_apply_method_is_non_void()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [ApplyMethodWithReturnType::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_throwing_exception_if_apply_method_is_static()
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(classesToResolve: [StaticApplyMethodExample::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }
}
