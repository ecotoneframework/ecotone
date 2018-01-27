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
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessagingConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDrivenConsumerFactory;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceNotFoundException;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Support\Assert;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\MessagingSystem;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrowConsumerFactory;
use SimplyCodedSoftware\Messaging\Future;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxy;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlingException;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;
use SimplyCodedSoftware\Messaging\MessageChannel;
use SimplyCodedSoftware\Messaging\PollableChannel;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    /**
     * @var array|MessageChannel[]
     */
    private $messageChannels = [];
    /**
     * @var GatewayProxy[]
     */
    private $gateways;
    /**
     * @var MessagingSystemConfiguration
     */
    private $messagingSystemConfiguration;
    /**
     * @var MessagingSystem
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
        $this->messagingSystemConfiguration = MessagingSystemConfiguration::prepare(
            $this->inMemoryReferenceSearchService,
            InMemoryModuleMessagingConfiguration::createEmpty()
        );
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
                $this->messageChannels[$channelName] = DirectChannel::create();
                $this->getMessagingSystemConfiguration()
                    ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, $this->messageChannels[$channelName]));
                break;
            }
            case "Pollable Channel": {
                $this->messageChannels[$channelName] = QueueChannel::create();
                $this->getMessagingSystemConfiguration()
                    ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, $this->messageChannels[$channelName]));
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
     * @Given I set gateway for :interfaceName and :methodName with request channel :requestChannel
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     */
    public function iSetGatewayForAndWithRequestChannel(string $interfaceName, string $methodName, string $requestChannel)
    {
        /** @var DirectChannel $messageChannel */
        $messageChannel = $this->getChannelByName($requestChannel);
        Assert::isSubclassOf($messageChannel, DirectChannel::class, "Request Channel for Direct Channel");

        $this->gateways = GatewayProxyBuilder::create($interfaceName, $methodName, $messageChannel);
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
        $gatewayProxyBuilder = $this->createGatewayBuilder($interfaceName, $methodName, $requestChannel);

        $this->gateways[$gatewayName] = $gatewayProxyBuilder
                                            ->build();
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
        /** @var PollableChannel $pollableChannel */
        $pollableChannel = $this->getChannelByName($replyChannel);
        Assert::isSubclassOf($pollableChannel, PollableChannel::class, "Reply channel for gateway must be pollable channel");

        $gatewayProxyBuilder = $this->createGatewayBuilder($interfaceName, $methodName, $requestChannel)
                                    ->withReplyChannel($pollableChannel)
                                    ->withMillisecondTimeout(1);

        $this->gateways[$gatewayName] = $gatewayProxyBuilder
            ->build();
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
                                    ->registerConsumerFactory(new EventDrivenConsumerFactory())
                                    ->registerConsumerFactory(new PollOrThrowConsumerFactory())
                                    ->buildMessagingSystemFromConfiguration();
    }

    /**
     * @param string $channelName
     * @return MessageChannel
     */
    private function getChannelByName(string $channelName) : MessageChannel
    {
        foreach ($this->messageChannels as $messageChannelName => $messageChannel) {
            if ($messageChannelName === $channelName) {
                return $messageChannel;
            }
        }

        throw new \InvalidArgumentException("Channel with name {$channelName} do not exists");
    }

    /**
     * @param string $gatewayNameToFind
     * @return mixed
     */
    private function getGatewayByName(string $gatewayNameToFind)
    {
        foreach ($this->gateways as $gatewayName => $gateway) {
            if ($gatewayName == $gatewayNameToFind) {
                return $gateway;
            }
        }

        throw new \InvalidArgumentException("Channel with name {$gatewayNameToFind} do not exists");
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     * @return \SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder
     */
    private function createGatewayBuilder(string $interfaceName, string $methodName, string $requestChannel): GatewayProxyBuilder
    {
        $messageChannel = $this->getChannelByName($requestChannel);
        /** @var DirectChannel $messageChannel */
        Assert::isSubclassOf($messageChannel, DirectChannel::class, "Request Channel should be Direct Channel");

        $gatewayProxyBuilder = GatewayProxyBuilder::create($interfaceName, $methodName, $messageChannel);
        return $gatewayProxyBuilder;
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
                                        ->withName($handlerName)
                                        ->withInputMessageChannel($channelName)
                                        ->setReferenceSearchService($this->inMemoryReferenceSearchService);
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
        $this->messagingSystem->runConsumerByName($consumerName);
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function getMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return $this->messagingSystemConfiguration;
    }
}
