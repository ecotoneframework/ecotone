<?php

namespace Test\Ecotone\Messaging\Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Exception;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use Ecotone\Messaging\Future;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\MessagingException;
use Test\Ecotone\Messaging\Fixture\Behat\Booking\BookingService;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway\CalculateGatewayExample;
use Test\Ecotone\Messaging\Fixture\Behat\InterceptedScheduled\InterceptedScheduledGateway;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\Order;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderConfirmation;
use Test\Ecotone\Messaging\Fixture\Behat\Ordering\OrderingService;
use Test\Ecotone\Messaging\Fixture\Behat\Shopping\BookWasReserved;
use Test\Ecotone\Messaging\Fixture\Behat\Shopping\ShoppingService;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    private MessagingSystemConfiguration $messagingSystemConfiguration;
    private ?\Ecotone\Messaging\Config\ConfiguredMessagingSystem $messagingSystem;
    private ?\Ecotone\Messaging\Handler\InMemoryReferenceSearchService $inMemoryReferenceSearchService;
    private ?\Ecotone\Messaging\Future $future;

    /**
     * @Given I register :bookingRequestName as :type
     * @param string $channelName
     * @param string $type
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iRegisterAs(string $channelName, string $type)
    {
        switch ($type) {
            case "Direct Channel":
                {
                    $this->getMessagingSystemConfiguration()
                        ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, DirectChannel::create()));
                    break;
                }
            case "Pollable Channel":
                {
                    $this->getMessagingSystemConfiguration()
                        ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, QueueChannel::create()));
                    break;
                }
        }
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function getMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return $this->messagingSystemConfiguration;
    }

    /**
     * @Given I activate service with name :endpointName for :className with method :methodName to listen on :channelName channel
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @throws Exception
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannel(string $className, string $methodName, string $channelName)
    {
        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler($this->createServiceActivatorBuilder(Uuid::uuid4()->toString(), $className, $methodName, $channelName));
    }

    /**
     * @param string $endpointName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @return ServiceActivatorBuilder
     */
    private function createServiceActivatorBuilder(string $endpointName, string $className, string $methodName, string $channelName): MessageHandlerBuilder
    {
        return ServiceActivatorBuilder::create($className, $methodName)
            ->withInputChannelName($channelName)
            ->withEndpointId($endpointName);
    }

    /**
     * @Given I activate service with name :endpointName for :className with method :methodName to listen on :channelName channel and output channel :outputChannel
     * @param string $endpointName
     * @param string $className
     * @param string $methodName
     * @param string $channelName
     * @param string $outputChannel
     * @throws Exception
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iActivateServiceWithNameForWithMethodToListenOnChannelAndOutputChannel(string $endpointName, string $className, string $methodName, string $channelName, string $outputChannel)
    {
        $this->registerReference($className);

        $this->getMessagingSystemConfiguration()->registerMessageHandler(
            $this->createServiceActivatorBuilder($endpointName, $className, $methodName, $channelName)
                ->withOutputMessageChannel($outputChannel)
        );
    }

    /**
     * @param string $className
     * @return null|object
     * @throws MessagingException
     */
    private function registerReference(string $className)
    {
        $this->inMemoryReferenceSearchService->registerReferencedObject($className, new $className());
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
                ->withReplyMillisecondTimeout(1)
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
     * @param string $gatewayNameToFind
     * @return object
     */
    private function getGatewayByName(string $gatewayNameToFind)
    {
        return $this->messagingSystem->getGatewayByName($gatewayNameToFind);
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

        Assert::assertEquals(true, $gateway->checkIfIsBooked($flatNumber));
    }

    /**
     * @Given I run messaging system
     */
    public function iRunMessagingSystem()
    {
        $this->messagingSystem = $this->getMessagingSystemConfiguration()
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->buildMessagingSystemFromConfiguration($this->inMemoryReferenceSearchService);
    }

    /**
     * @Given I activate transformer with name :name for :className and :methodName with request channel :requestChannelName and output channel :responseChannelName
     * @param string $name
     * @param string $className
     * @param string $methodName
     * @param string $requestChannelName
     * @param string $responseChannelName
     * @throws Exception
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iActivateTransformerWithNameForAndWithRequestChannelAndOutputChannel(string $name, string $className, string $methodName, string $requestChannelName, string $responseChannelName)
    {
        $inputChannel = $requestChannelName;
        $outputChannel = $responseChannelName;
        $this->registerReference($className);

        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler(
                TransformerBuilder::create($className, $methodName)
                    ->withInputChannelName($inputChannel)
                    ->withOutputMessageChannel($outputChannel)
            );
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

        Assert::assertInstanceOf(BookWasReserved::class, $bookWasReserved, "Book must be reserved");
    }

    /**
     * @Given I activate header router with name :endpointName and input Channel :inputChannelName for header :headerName with mapping:
     * @param string $endpointName
     * @param string $inputChannelName
     * @param string $headerName
     * @param TableNode $mapping
     * @throws Exception
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iActivateHeaderRouterWithNameAndInputChannelForHeaderWithMapping(string $endpointName, string $inputChannelName, string $headerName, TableNode $mapping)
    {
        $channelToValue = [];
        foreach ($mapping->getHash() as $headerValue) {
            $channelToValue[$headerValue['value']] = $headerValue['target_channel'];
        }

        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler(
                RouterBuilder::createHeaderMappingRouter($headerName, $channelToValue)
                    ->withEndpointId($endpointName)
                    ->withInputChannelName($inputChannelName)
            );
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
            Assert::assertTrue(false, "Expect exception got none");
        } catch (Exception $e) {
        }
    }

    /**
     * @Then I should receive confirmation
     */
    public function iShouldReceiveConfirmation()
    {
        Assert::assertInstanceOf(OrderConfirmation::class, $this->future->resolve());
    }

    /**
     * @Then I expect exception during confirmation receiving
     */
    public function iExpectExceptionDuringConfirmationReceiving()
    {
        try {
            $this->future->resolve();
            Assert::assertTrue(false, "Expect exception got none");
        } catch (MessageHandlingException $e) {
        }
    }

    /**
     * @Given I activate header enricher transformer with name :endpointName with request channel :requestChannelName and output channel :outputChannelName with headers:
     * @param string $endpointName
     * @param string $requestChannelName
     * @param string $outputChannelName
     * @param TableNode $headers
     * @throws Exception
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function iActivateHeaderEnricherTransformerWithNameWithRequestChannelAndOutputChannelWithHeaders(string $endpointName, string $requestChannelName, string $outputChannelName, TableNode $headers)
    {
        $keyValues = [];
        foreach ($headers->getHash() as $keyValue) {
            $keyValues[$keyValue['key']] = $keyValue['value'];
        }

        $this->getMessagingSystemConfiguration()
            ->registerMessageHandler(
                TransformerBuilder::createHeaderEnricher(
                    $keyValues
                )
                    ->withEndpointId($endpointName)
                    ->withInputChannelName($requestChannelName)
                    ->withOutputMessageChannel($outputChannelName)
            );
    }

    /**
     * @When :consumerName handles message
     * @param string $consumerName
     */
    public function handlesMessage(string $consumerName)
    {
        $this->messagingSystem->run($consumerName);
    }

    /**
     * @When :arg1 handles message with exception
     */
    public function handlesMessageWithException(string $consumerName)
    {
        try {
            $this->handlesMessage($consumerName);
        }catch (\Exception $e) {
            return;
        }

        Assert::assertTrue(false,"Exception was not thrown");
    }

    /**
     * @Given I configure messaging system
     */
    public function iConfigureMessagingSystem()
    {
        $this->inMemoryReferenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $this->messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @When I call :gatewayName with :beginningValue I should receive :result
     */
    public function iCallWithIShouldReceive(string $gatewayName, int $beginningValue, int $result)
    {
        Assert::assertEquals($result, AnnotationBasedMessagingContext::getGateway($gatewayName)->calculate($beginningValue));
    }
}
