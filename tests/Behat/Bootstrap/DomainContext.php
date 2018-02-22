<?php

namespace Behat\Bootstrap;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Fixture\Behat\Booking\BookingService;
use Fixture\Behat\Ordering\Order;
use Fixture\Behat\Ordering\OrderConfirmation;
use Fixture\Behat\Ordering\OrderingService;
use Fixture\Behat\Shopping\BookWasReserved;
use Fixture\Behat\Shopping\ShoppingService;
use Fixture\Configuration\DumbConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessagingConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Future;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\PollableChannel;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    /**
     * @var MessagingSystemConfiguration
     */
    private $messagingSystemConfiguration;
    /**
     * @var ConfiguredMessagingSystem
     */
    private $messagingSystem;
    /**
     * @var InMemoryReferenceSearchService
     */
    private $inMemoryReferenceSearchService;
    /**
     * @var Future
     */
    private $future;

    public function __construct()
    {
        $this->inMemoryReferenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $this->messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());
    }

    /**
     * @Given I register :bookingRequestName as :type
     * @param string $channelName
     * @param string $type
     */
    public function iRegisterAs(string $channelName, string $type)
    {
        switch ($type) {
            case "Direct Channel": {
                $this->getMessagingSystemConfiguration()
                    ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, DirectChannel::create()));
                break;
            }
            case "Pollable Channel": {
                $this->getMessagingSystemConfiguration()
                    ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, QueueChannel::create()));
                break;
            }
        }
    }

    /**
     * @Given I activate service with name :handlerName for :className with method :methodName to listen on :channelName channel
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannel(string $handlerName, string $className, string $methodName, string $channelName)
    {
        $this->getMessagingSystemConfiguration()
                ->registerMessageHandler($this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName));
    }

    /**
     * @Given I activate service with name :handlerName for :className with method :methodName to listen on :channelName channel and output channel :outputChannel
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @param string $outputChannel
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannelAndOutputChannel(string $handlerName, string $className, string $methodName, string $channelName, string $outputChannel)
    {
        $this->registerReference($className);

        $this->getMessagingSystemConfiguration()->registerMessageHandler(
            $this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName)
                ->withOutputChannel($outputChannel)
        );
    }

    /**
     * @Given I activate gateway with name :gatewayName for :interfaceName and :methodName with request channel :requestChannel
     * @param string $gatewayName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     */
    public function iActivateGatewayWithNameForAndWithRequestChannel(string $gatewayName, string $interfaceName, string $methodName, string $requestChannel)
    {
        $this->messagingSystemConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create($gatewayName, $interfaceName, $methodName, $requestChannel)
        );
    }

    /**
     * @Given I activate gateway with name :gatewayName for :interfaceName and :methodName with request channel :requestChannel and reply channel :replyChannel
     * @param string $gatewayName
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     * @param string $replyChannel
     */
    public function iActivateGatewayWithNameForAndWithRequestChannelAndReplyChannel(string $gatewayName, string $interfaceName, string $methodName, string $requestChannel, string $replyChannel)
    {
        $this->messagingSystemConfiguration->registerGatewayBuilder(
            GatewayProxyBuilder::create($gatewayName, $interfaceName, $methodName, $requestChannel)
                ->withReplyChannel($replyChannel)
                ->withMillisecondTimeout(1)
        );
    }

    /**
     * @When I book flat with id :flatNumber using gateway :gatewayName
     * @param int $flatNumber
     * @param string $gatewayName
     */
    public function iBookFlatWithIdUsingGateway(int $flatNumber, string $gatewayName)
    {
        /** @var BookingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $gateway->bookFlat($flatNumber);
    }

    /**
     * @Then flat with id :flatNumber should be reserved when checked by :gatewayName
     * @param int $flatNumber
     * @param string $gatewayName
     */
    public function flatWithIdShouldBeReservedWhenCheckedBy(int $flatNumber, string $gatewayName)
    {
        /** @var BookingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        \PHPUnit\Framework\Assert::assertEquals(true, $gateway->checkIfIsBooked($flatNumber));
    }

    /**
     * @Given I run messaging system
     */
    public function iRunMessagingSystem()
    {
        $this->messagingSystem = $this->getMessagingSystemConfiguration()
                                    ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
                                    ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
                                    ->buildMessagingSystemFromConfiguration($this->inMemoryReferenceSearchService);
    }

    /**
     * @param string $gatewayNameToFind
     * @return object
     */
    private function getGatewayByName(string $gatewayNameToFind)
    {
        return $this->messagingSystem->getGatewayByName($gatewayNameToFind);
    }

    /**
     * @param string $handlerName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @return MessageHandlerBuilder
     */
    private function createServiceActivatorBuilder(string $handlerName, string $className, string $methodName, string $channelName): MessageHandlerBuilder
    {
        return ServiceActivatorBuilder::create($className, $methodName)
                                        ->withConsumerName($handlerName)
                                        ->withInputMessageChannel($channelName);
    }

    /**
     * @Given I activate transformer with name :name for :className and :methodName with request channel :requestChannelName and output channel :responseChannelName
     * @param string $name
     * @param string $className
     * @param string $methodName
     * @param string $requestChannelName
     * @param string $responseChannelName
     */
    public function iActivateTransformerWithNameForAndWithRequestChannelAndOutputChannel(string $name, string $className, string $methodName, string $requestChannelName, string $responseChannelName)
    {
        $inputChannel = $requestChannelName;
        $outputChannel = $responseChannelName;
        $this->registerReference($className);

        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler(TransformerBuilder::create($inputChannel, $outputChannel, $className, $methodName, $name));
    }

    /**
     * @When I reserve book named :bookName using gateway :gatewayName
     * @param string $bookName
     * @param string $gatewayName
     */
    public function iReserveBookNamedUsingGateway(string $bookName, string $gatewayName)
    {
        /** @var ShoppingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $bookWasReserved = $gateway->reserve($bookName);

        \PHPUnit\Framework\Assert::assertInstanceOf(BookWasReserved::class, $bookWasReserved, "Book must be reserved");
    }

    /**
     * @param string $className
     * @return null|object
     */
    private function registerReference(string $className)
    {
        $this->inMemoryReferenceSearchService->registerReferencedObject($className, new $className());
    }

    /**
     * @Given I activate header router with name :handlerName and input Channel :inputChannelName for header :headerName with mapping:
     * @param string $handlerName
     * @param string $inputChannelName
     * @param string $headerName
     * @param TableNode $mapping
     */
    public function iActivateHeaderRouterWithNameAndInputChannelForHeaderWithMapping(string $handlerName, string $inputChannelName, string $headerName, TableNode $mapping)
    {
        $channelToValue = [];
        foreach ($mapping->getHash() as $headerValue) {
            $channelToValue[$headerValue['value']] = $headerValue['target_channel'];
        }

        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler(RouterBuilder::createHeaderValueRouter($handlerName, $inputChannelName, $headerName, $channelToValue));
    }

    /**
     * @When I send order request with id :orderId product name :productName using gateway :gatewayName
     * @param int $orderId
     * @param string $productName
     * @param string $gatewayName
     */
    public function iSendOrderRequestWithIdProductNameUsingGateway(int $orderId, string $productName, string $gatewayName)
    {
        /** @var OrderingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);

        $this->future = $gateway->processOrder(Order::create($orderId, $productName));
    }

    /**
     * @When I expect exception when sending order request with id :orderId product name :productName using gateway :gatewayName
     * @param int $orderId
     * @param string $productName
     * @param string $gatewayName
     */
    public function iExpectExceptionWhenSendingOrderRequestWithIdProductNameUsingGateway(int $orderId, string $productName, string $gatewayName)
    {
        /** @var OrderingService $gateway */
        $gateway = $this->getGatewayByName($gatewayName);


        try {
            $gateway->processOrder(Order::create($orderId, $productName));
            \PHPUnit\Framework\Assert::assertTrue(false, "Expect exception got none");
        }catch (\Exception $e) {}
    }

    /**
     * @Then I should receive confirmation
     */
    public function iShouldReceiveConfirmation()
    {
        \PHPUnit\Framework\Assert::assertInstanceOf(OrderConfirmation::class, $this->future->resolve());
    }

    /**
     * @Then I expect exception during confirmation receiving
     */
    public function iExpectExceptionDuringConfirmationReceiving()
    {
        try {
            $this->future->resolve();
            \PHPUnit\Framework\Assert::assertTrue(false, "Expect exception got none");
        }catch (MessageHandlingException $e) {}
    }

    /**
     * @Given I activate header enricher transformer with name :handlerName with request channel :requestChannelName and output channel :outputChannelName with headers:
     * @param string $handlerName
     * @param string $requestChannelName
     * @param string $outputChannelName
     * @param TableNode $headers
     */
    public function iActivateHeaderEnricherTransformerWithNameWithRequestChannelAndOutputChannelWithHeaders(string $handlerName, string $requestChannelName, string $outputChannelName, TableNode $headers)
    {
        $keyValues = [];
        foreach ($headers->getHash() as $keyValue) {
            $keyValues[$keyValue['key']] = $keyValue['value'];
        }

        $this->getMessagingSystemConfiguration()
                ->registerMessageHandler(TransformerBuilder::createHeaderEnricher(
                    $handlerName,
                    $requestChannelName,
                    $outputChannelName,
                    $keyValues
        ));
    }

    /**
     * @When :consumerName handles message
     * @param string $consumerName
     */
    public function handlesMessage(string $consumerName)
    {
        $this->messagingSystem->runSeparatelyRunningConsumerBy($consumerName);
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function getMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return $this->messagingSystemConfiguration;
    }
}
