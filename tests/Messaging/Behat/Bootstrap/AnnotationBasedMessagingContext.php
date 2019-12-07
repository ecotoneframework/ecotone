<?php


namespace Test\Ecotone\Messaging\Behat\Bootstrap;


use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Lite\EcotoneLiteConfiguration;
use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\ProxyFactory;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\CommandBusWithEventPublishing;
use Ecotone\Modelling\QueryBus;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\InMemoryStandardRepository;
use Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate\OrderNotificator;
use Test\Ecotone\Modelling\Fixture\ProxyEventBusFromMessagingSystem;
use Test\Ecotone\Modelling\Fixture\ProxyLazyEventBusFromMessagingSystem;
use Test\Ecotone\Modelling\Fixture\Renter\AppointmentStandardRepository;
use Test\Ecotone\Modelling\Fixture\Renter\CreateAppointmentCommand;
use Test\Ecotone\Modelling\Fixture\Renter\RentCalendar;
use Test\Ecotone\Modelling\Fixture\TestingLazyEventBus;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\InboundCalculation;
use Test\Ecotone\Messaging\Fixture\Behat\Calculating\ResultService;

class AnnotationBasedMessagingContext implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private static $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     * @param string $namespace
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        switch ($namespace) {
            case "Test\Ecotone\Modelling\Fixture\Renter": {
                $objects = [
                      RentCalendar::class => new RentCalendar(),
                      AppointmentStandardRepository::class => AppointmentStandardRepository::createEmpty()
                ];
                break;
            }
            case "Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate": {
                $objects = [
                    OrderNotificator::class => new OrderNotificator(),
                    InMemoryStandardRepository::class => InMemoryStandardRepository::createEmpty()
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

        $cacheDirectoryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "ecotone_testing_behat_cache";

        $applicationConfiguration = ApplicationConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectoryPath)
            ->withNamespaces([$namespace]);

        MessagingSystemConfiguration::cleanCache($applicationConfiguration);
        self::$messagingSystem = EcotoneLiteConfiguration::createWithConfiguration(
            __DIR__ . "/../../../../",
            InMemoryPSRContainer::createFromObjects($objects),
            $applicationConfiguration
        );
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
        self::$messagingSystem->runSeparatelyRunningEndpointBy("inboundCalculator");
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
        Assert::assertTrue(self::getQueryBus()->convertAndSend("doesCalendarContainAppointments", MediaType::APPLICATION_X_PHP, $appointmentId));
    }

    /**
     * @return CommandBus
     */
    public static function getCommandBus(): CommandBus
    {
        return self::$messagingSystem->getGatewayByName(CommandBusWithEventPublishing::class);
    }

    /**
     * @return QueryBus
     */
    public static function getQueryBus(): QueryBus
    {
        return self::$messagingSystem->getGatewayByName(QueryBus::class);
    }
}
