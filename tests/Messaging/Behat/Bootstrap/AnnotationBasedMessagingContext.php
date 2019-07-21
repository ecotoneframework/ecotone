<?php


namespace Test\SimplyCodedSoftware\Messaging\Behat\Bootstrap;


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\Assert;
use SimplyCodedSoftware\DomainModel\CommandBus;
use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\DomainModel\LazyEventBus\LazyEventBus;
use SimplyCodedSoftware\DomainModel\QueryBus;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\ChangeShippingAddressCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\CreateOrderCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetOrderAmountQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\GetShippingAddressQuery;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\InMemoryAggregateRepository;
use Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Test\SimplyCodedSoftware\DomainModel\Fixture\ProxyEventBusFromMessagingSystem;
use Test\SimplyCodedSoftware\DomainModel\Fixture\ProxyLazyEventBusFromMessagingSystem;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Renter\AppointmentRepository;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Renter\CreateAppointmentCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Renter\RentCalendar;
use Test\SimplyCodedSoftware\DomainModel\Fixture\TestingLazyEventBus;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\InboundCalculation;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\ResultService;

class AnnotationBasedMessagingContext implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private static $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     * @param string $namespace
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        switch ($namespace) {
            case "Test\SimplyCodedSoftware\DomainModel\Fixture\Renter": {
                $objects = [
                      RentCalendar::class => new RentCalendar(),
                      AppointmentRepository::class => AppointmentRepository::createEmpty()
                ];
                break;
            }
            case "Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate": {
                $objects = [
                    OrderNotificator::class => new OrderNotificator(),
                    InMemoryAggregateRepository::class => InMemoryAggregateRepository::createEmpty()
                ];
                break;
            }
            default: {
                $objects = [
                    InboundCalculation::class => new InboundCalculation(),
                    ResultService::class => new ResultService(),
                    CalculatorInterceptor::class => new CalculatorInterceptor()
                ];
                break;
            }
        }

        $messagingConfiguration = MessagingSystemConfiguration::createWithCachedReferenceObjectsForNamespaces(
            __DIR__ . "/../../../../",
            [$namespace],
            InMemoryReferenceTypeFromNameResolver::createFromObjects($objects),
            "test",
            true,
            true
        );

        self::$messagingSystem = $messagingConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith($objects));
    }

    /**
     * @When I calculate for :amount using gateway
     * @param int $amount
     */
    public function iCalculateForUsingGateway(int $amount)
    {
        /** @var Calculator $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(Calculator::class);

        $gateway->calculate($amount);
    }

    /**
     * @Then the result should be :amount
     * @param int $amount
     * @throws ConfigurationException
     */
    public function theResultShouldBe(int $amount)
    {
        /** @var PollableChannel $resultChannel */
        $resultChannel = self::$messagingSystem->getMessageChannelByName("resultChannel");

        $message = $resultChannel->receive();
        Assert::assertNotNull($message, "Result was never received");
        Assert::assertEquals($amount, $message->getPayload());
    }

    /**
     * @When I calculate for :amount using gateway then result should be :result
     * @param int $amount
     * @param int $result
     */
    public function iCalculateForUsingGatewayThenResultShouldBe(int $amount, int $result)
    {
        /** @var Calculator $gateway */
        $gateway = self::$messagingSystem->getGatewayByName(Calculator::class);

        Assert::assertEquals($result, $gateway->calculate($amount));
    }

    /**
     * @When I calculate using inbound channel adapter
     */
    public function iCalculateUsingInboundChannelAdapter()
    {
        self::$messagingSystem->runSeparatelyRunningConsumerBy("inboundCalculator");
    }

    /**
     * @Then result should be :result in :channelName channel
     * @param $result
     * @param string $channelName
     * @throws ConfigurationException
     */
    public function resultShouldBeInChannel($result, string $channelName)
    {
        /** @var PollableChannel $messageChannel */
        $messageChannel = self::$messagingSystem->getMessageChannelByName($channelName);

        Assert::assertEquals($result, $messageChannel->receive()->getPayload());
    }

    /**
     * @When I rent appointment with id :appointmentId and duration :duration
     * @param int $appointmentId
     * @param int $duration
     */
    public function iRentAppointmentWithIdAndDuration(int $appointmentId, int $duration)
    {
        self::getCommandBus()->send(new CreateAppointmentCommand($appointmentId, $duration));
    }

    /**
     * @Then calendar should contain event with appointment id :appointmentId
     * @param int $appointmentId
     */
    public function calendarShouldContainEventWithAppointmentId(int $appointmentId)
    {
        Assert::assertTrue(self::getQueryBus()->convertAndSend("doesCalendarContainAppointments", MediaType::APPLICATION_X_PHP_OBJECT, $appointmentId));
    }

    /**
     * @return CommandBus
     */
    public static function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBus::class);
    }

    /**
     * @return QueryBus
     */
    public static function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }
}
