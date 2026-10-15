<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\InMemoryStateStoredRepository;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleStateStoredRepository;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\Job;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\StartJob;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\Order;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryBuilder;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStateStoredRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class AggregateRepositoriesTest extends TestCase
{
    public function test_returning_default_standard_repository_if_there_is_one()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class, InMemoryStateStoredRepository::class],
                [InMemoryStateStoredRepository::class => InMemoryStateStoredRepository::createEmpty()],
                self::ordersChannelConfiguration()
                    ->withEnabledModulePackageNames([]),
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
            ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
            ->run('orders')
            ->getAggregate(Order::class, '123')
        );
    }

    public function test_returning_default_event_sourced_repository_if_there_is_one()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Job::class, InMemoryEventSourcedRepository::class],
                [InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty()],
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
                configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
            )
                ->sendCommand(new StartJob('123'))
                ->getAggregate(Job::class, '123')
        );
    }

    public function test_throwing_if_no_event_sourced_repository_available_and_multiple_state_based_repositories_are(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [Job::class, InMemoryStateStoredRepository::class],
            [InMemoryStateStoredRepository::class => InMemoryStateStoredRepository::createEmpty()],
            addInMemoryEventSourcedRepository: false,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        self::expectException(InvalidArgumentException::class);

        $ecotone->sendCommand(new StartJob('123'));
    }

    public function test_throwing_error_if_no_standard_repository_available_and_multiple_event_sourced_repositories(): void
    {
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [Order::class, InMemoryEventSourcedRepository::class],
            [InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty()],
            self::ordersChannelConfiguration()
                ->withEnabledModulePackageNames([]),
            addInMemoryStateStoredRepository: false,
        );

        self::expectException(InvalidArgumentException::class);

        $ecotone
            ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
            ->run('orders');
    }

    public function test_throwing_exception_if_only_event_soured_repository_available_for_standard_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [Order::class, InMemoryEventSourcedRepository::class],
            [InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty()],
            self::ordersChannelConfiguration()
                ->withEnabledModulePackageNames([]),
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
            ->run('orders');
    }

    public function test_throwing_exception_if_only_standard_repository_available_for_event_sourced_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [Job::class, InMemoryStateStoredRepository::class],
            [InMemoryStateStoredRepository::class => InMemoryStateStoredRepository::createEmpty()],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        )
            ->sendCommand(new StartJob('123'));
    }

    public function test_returning_standard_repository_in_case_there_are_two_types()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class, InMemoryStateStoredRepository::class, InMemoryEventSourcedRepository::class],
                [
                    InMemoryStateStoredRepository::class => InMemoryStateStoredRepository::createEmpty(),
                    InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty(),
                ],
                self::ordersChannelConfiguration()
                    ->withEnabledModulePackageNames([]),
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
                ->run('orders')
                ->getAggregate(Order::class, '123')
        );
    }

    public function test_returning_event_sourced_repository_in_case_there_are_two_types()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class, InMemoryStateStoredRepository::class, InMemoryEventSourcedRepository::class],
                [
                    InMemoryStateStoredRepository::class => InMemoryStateStoredRepository::createEmpty(),
                    InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty(),
                ],
                self::ordersChannelConfiguration()
                    ->withEnabledModulePackageNames([]),
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
                ->run('orders')
                ->getAggregate(Order::class, '123')
        );
    }

    public function test_retrieving_correct_repository_according_to_handled_aggregates()
    {
        $articleRepository = InMemoryArticleStateStoredRepository::createWith([Article::createWith(PublishArticleCommand::createWith('test', 'title', 'test'))]);

        EcotoneLite::bootstrapFlowTesting(
            [Article::class, AppointmentStateStoredRepository::class, InMemoryArticleStateStoredRepository::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createEmpty(),
                InMemoryArticleStateStoredRepository::class => $articleRepository,
            ],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        )
            ->sendCommand(PublishArticleCommand::createWith('test', 'title', 'test'));

        self::assertNotNull(
            $articleRepository->findBy(Article::class, ['author' => 'test', 'title' => 'title'])
        );
    }

    public function test_throwing_exception_if_repository_handling_different_type()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [Order::class, AppointmentStateStoredRepository::class, InMemoryArticleStateStoredRepository::class],
            [
                AppointmentStateStoredRepository::class => AppointmentStateStoredRepository::createEmpty(),
                InMemoryArticleStateStoredRepository::class => InMemoryArticleStateStoredRepository::createEmpty(),
            ],
            self::ordersChannelConfiguration()
                ->withEnabledModulePackageNames([]),
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommandWithRouting('order.register', new PlaceOrder('123'))
            ->run('orders')
        ;
    }

    public function test_returning_built_repository_builder()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Appointment::class],
                configuration: ServiceConfiguration::createWithDefaults()
                    ->withExtensionObjects([AppointmentRepositoryBuilder::createEmpty()])
                    ->withEnabledModulePackageNames([]),
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommand(new CreateAppointmentCommand('123', 1))
                ->getAggregate(Appointment::class, '123')
        );
    }

    private static function ordersChannelConfiguration(): ServiceConfiguration
    {
        return ServiceConfiguration::createWithDefaults()
            ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('orders'));
    }
}
