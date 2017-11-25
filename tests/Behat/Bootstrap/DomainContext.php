<?php

namespace Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Fixture\Behat\Booking\BookingService;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
use Messaging\Config\GatewayProxyBuilder;
use Messaging\Config\MessagingSystem;
use Messaging\Config\ServiceActivatorBuilder;
use Messaging\Endpoint\ConsumerEndpointFactory;
use Messaging\Endpoint\ConsumerLifecycle;
use Messaging\Handler\Gateway\GatewayProxy;
use Messaging\MessageChannel;
use Messaging\PollableChannel;
use Messaging\Support\Assert;

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
     * @var array|ConsumerLifecycle
     */
    private $consumers = [];
    /**
     * @var ConsumerEndpointFactory
     */
    private $consumerEndpointFactory;
    /**
     * @var GatewayProxy[]
     */
    private $gateways;
    /**
     * @var MessagingSystem
     */
    private $messagingSystem;

    /**
     * @var object[]
     */
    private $serviceObjects = [];


    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
        $this->consumerEndpointFactory = new ConsumerEndpointFactory();
    }

    /**
     * @Given I register :bookingRequestName as :type
     * @param string $bookingRequestName
     * @param string $type
     */
    public function iRegisterAs(string $bookingRequestName, string $type)
    {
        switch ($type) {
            case "Direct Channel": {
                $this->messageChannels[$bookingRequestName] = DirectChannel::create();
                break;
            }
            case "Pollable Channel": {
                $this->messageChannels[$bookingRequestName] = QueueChannel::create();
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
        $serviceActivatorBuilder = $this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName);

        $this->consumers[] = $this->consumerEndpointFactory->create($serviceActivatorBuilder);
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
        $serviceActivatorBuilder = $this->createServiceActivatorBuilder($handlerName, $className, $methodName, $channelName)
                                        ->withOutputChannel($this->getChannelByName($outputChannel));

        $this->consumers[] = $this->consumerEndpointFactory->create($serviceActivatorBuilder);
    }

    /**
     * @Given I set gateway for :interfaceName and :methodName with request channel :requestChannel
     * @param string $interfaceName
     * @param string $methodName
     * @param string $requestChannel
     */
    public function iSetGatewayForAndWithRequestChannel(string $interfaceName, string $methodName, string $requestChannel)
    {
        $messageChannel = $this->getChannelByName($requestChannel);
        /** @var DirectChannel $messageChannel */
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
     * @return GatewayProxyBuilder
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
     * @return ServiceActivatorBuilder
     */
    private function createServiceActivatorBuilder(string $handlerName, string $className, string $methodName, string $channelName): ServiceActivatorBuilder
    {
        $object = null;
        if (array_key_exists($className, $this->serviceObjects)) {
            $object = $this->serviceObjects[$className];
        }else {
            $object = new $className();
            $this->serviceObjects[$className] = $object;
        }

        $serviceActivatorBuilder = ServiceActivatorBuilder::create($object, $methodName);
        $serviceActivatorBuilder->withInputMessageChannel($this->getChannelByName($channelName));
        $serviceActivatorBuilder->withName($handlerName);

        return $serviceActivatorBuilder;
    }

    /**
     * @Given I run messaging system
     */
    public function iRunMessagingSystem()
    {
        $this->messagingSystem = MessagingSystem::create($this->consumers);

        $this->messagingSystem->runEventDrivenConsumers();
    }
}
