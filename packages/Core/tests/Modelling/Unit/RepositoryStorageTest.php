<?php


namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\InMemoryEventSourcedRepository;
use Ecotone\Modelling\RepositoryStorage;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Blog\Article;
use Test\Ecotone\Modelling\Fixture\Blog\InMemoryArticleStandardRepository;
use Test\Ecotone\Modelling\Fixture\Blog\PublishArticleCommand;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\OrderAggregate\Order;
use Test\Ecotone\Modelling\Fixture\Renter\Appointment;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentRepositoryBuilder;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;

class RepositoryStorageTest extends TestCase
{
    public function test_returning_default_standard_repository_if_there_is_one()
    {
        $repository = InMemoryStandardRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                  InMemoryStandardRepository::class => $repository
            ]), [InMemoryStandardRepository::class]
        );

        $this->assertEquals(
            $repository,
            $repositoryStorage->getRepository()
        );
    }

    public function test_returning_default_event_sourced_repository_if_there_is_one()
    {
        $repository = InMemoryEventSourcedRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, true, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            InMemoryEventSourcedRepository::class => $repository
        ]), [InMemoryEventSourcedRepository::class]
        );

        $this->assertEquals(
            $repository,
            $repositoryStorage->getRepository()
        );
    }

    public function test_throwing_exception_if_only_event_soured_repository_available_for_standard_aggregate()
    {
        $repository = InMemoryEventSourcedRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            InMemoryEventSourcedRepository::class => $repository
        ]), [InMemoryEventSourcedRepository::class]
        );

        $this->expectException(InvalidArgumentException::class);

        $repositoryStorage->getRepository();
    }

    public function test_throwing_exception_if_only_standard_repository_available_for_event_sourced_aggregate()
    {
        $repository = InMemoryStandardRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, true, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            InMemoryStandardRepository::class => $repository
        ]), [InMemoryStandardRepository::class]
        );

        $this->expectException(InvalidArgumentException::class);

        $repositoryStorage->getRepository();
    }

    public function test_returning_standard_repository_in_case_there_are_two_types()
    {
        $repository = InMemoryStandardRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            InMemoryEventSourcedRepository::class => InMemoryEventSourcedRepository::createEmpty(),
            InMemoryStandardRepository::class => $repository
        ]), [InMemoryStandardRepository::class, InMemoryEventSourcedRepository::class]
        );

        $this->assertEquals(
            $repository,
            $repositoryStorage->getRepository()
        );
    }

    public function test_returning_event_sourced_repository_in_case_there_are_two_types()
    {
        $repository = InMemoryEventSourcedRepository::createEmpty();

        $repositoryStorage = new RepositoryStorage(
            Order::class, true, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
                InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty(),
                InMemoryEventSourcedRepository::class => $repository
        ]), [InMemoryEventSourcedRepository::class, InMemoryStandardRepository::class]
        );

        $this->assertEquals(
            $repository,
            $repositoryStorage->getRepository()
        );
    }

    public function test_retrieving_correct_repository_according_to_handled_aggregates()
    {
        $repository = InMemoryArticleStandardRepository::createWith([Article::createWith(PublishArticleCommand::createWith("test", "title", "test"))]);

        $repositoryStorage = new RepositoryStorage(
            Article::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            "incorrect" => AppointmentStandardRepository::createEmpty(),
            "correct" => $repository
        ]), ["incorrect", "correct"]
        );

        $this->assertEquals(
            $repository,
            $repositoryStorage->getRepository()
        );
    }

    public function test_throwing_exception_if_repository_handling_different_type()
    {
        $repositoryStorage = new RepositoryStorage(
            Article::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            1 => InMemoryEventSourcedRepository::createEmpty(),
            2 => InMemoryEventSourcedRepository::createEmpty()
        ]), [1, 2]
        );

        $this->expectException(InvalidArgumentException::class);

        $repositoryStorage->getRepository();
    }

    public function test_throwing_exception_if_aggregate_was_not_found_when_multiple_of_same_type_are_registered()
    {
        $repository = InMemoryArticleStandardRepository::createWith([Article::createWith(PublishArticleCommand::createWith("test", "title", "test"))]);

        $repositoryStorage = new RepositoryStorage(
            \stdClass::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            "incorrect" => AppointmentStandardRepository::createEmpty(),
            "correct" => $repository
        ]), ["incorrect", "correct"]
        );

        $this->expectException(InvalidArgumentException::class);

        $repositoryStorage->getRepository();
    }

    public function test_returning_built_repository_builder()
    {
        $repositoryStorage = new RepositoryStorage(
            Appointment::class, false, InMemoryChannelResolver::createEmpty(), InMemoryReferenceSearchService::createWith([
            AppointmentRepositoryBuilder::class => AppointmentRepositoryBuilder::createEmpty()
        ]), [AppointmentRepositoryBuilder::class]
        );

        $this->assertEquals(
            AppointmentStandardRepository::createEmpty(),
            $repositoryStorage->getRepository()
        );
    }
}