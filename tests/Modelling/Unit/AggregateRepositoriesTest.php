<?php

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleStandardRepository;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\Job;
use Test\Ecotone\Modelling\Fixture\EventSourcedAggregateWithInternalEventRecorder\StartJob;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\Order;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryBuilder;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
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
                [Order::class, InMemoryStandardRepository::class],
                [InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty()],
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
            ->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'))
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
            )
                ->sendCommand(new StartJob('123'))
                ->getAggregate(Job::class, '123')
        );
    }

    public function test_throwing_exception_if_only_event_soured_repository_available_for_standard_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [Order::class, InMemoryEventSourcedRepository::class],
            [InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty()],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'));
    }

    public function test_throwing_exception_if_only_standard_repository_available_for_event_sourced_aggregate()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapFlowTesting(
            [Job::class, InMemoryStandardRepository::class],
            [InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty()],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommand(new StartJob('123'));
    }

    public function test_returning_standard_repository_in_case_there_are_two_types()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class, InMemoryStandardRepository::class, InMemoryEventSourcedRepository::class],
                [
                    InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty(),
                    InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty(),
                ],
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'))
                ->getAggregate(Order::class, '123')
        );
    }

    public function test_returning_event_sourced_repository_in_case_there_are_two_types()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Order::class, InMemoryStandardRepository::class, InMemoryEventSourcedRepository::class],
                [
                    InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty(),
                    InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty(),
                ],
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'))
                ->getAggregate(Order::class, '123')
        );
    }

    public function test_retrieving_correct_repository_according_to_handled_aggregates()
    {
        $articleRepository = InMemoryArticleStandardRepository::createWith([Article::createWith(PublishArticleCommand::createWith('test', 'title', 'test'))]);

        EcotoneLite::bootstrapFlowTesting(
            [Article::class, AppointmentStandardRepository::class, InMemoryArticleStandardRepository::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
                InMemoryArticleStandardRepository::class => $articleRepository,
            ],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
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
            [Order::class, AppointmentStandardRepository::class, InMemoryArticleStandardRepository::class],
            [
                AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty(),
                InMemoryArticleStandardRepository::class => InMemoryArticleStandardRepository::createEmpty(),
            ],
            addInMemoryStateStoredRepository: false,
            addInMemoryEventSourcedRepository: false,
        )
            ->sendCommandWithRoutingKey('order.register', new PlaceOrder('123'))
        ;
    }

    public function test_returning_built_repository_builder()
    {
        self::assertNotNull(
            EcotoneLite::bootstrapFlowTesting(
                [Appointment::class],
                configuration: ServiceConfiguration::createWithDefaults()
                    ->withExtensionObjects([AppointmentRepositoryBuilder::createEmpty()]),
                addInMemoryStateStoredRepository: false,
                addInMemoryEventSourcedRepository: false,
            )
                ->sendCommand(new CreateAppointmentCommand('123', 1))
                ->getAggregate(Appointment::class, '123')
        );
    }
}
