<?php


namespace Test\SimplyCodedSoftware\Messaging\Behat\Bootstrap;


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Doctrine\Common\Annotations\AnnotationException;
use PHPUnit\Framework\Assert;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\MessagingException;
use SimplyCodedSoftware\Messaging\PollableChannel;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\Calculator;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\CalculatorInterceptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\InboundCalculation;
use Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating\ResultService;

class AnnotationBasedMessagingContext implements Context
{
    /**
     * @var ConfiguredMessagingSystem
     */
    private $messagingSystem;

    /**
     * @Given I active messaging for namespace :namespace
     * @param string $namespace
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     */
    public function iActiveMessagingForNamespace(string $namespace)
    {
        $objects = [
            InboundCalculation::class => new InboundCalculation(),
            ResultService::class => new ResultService(),
            CalculatorInterceptor::class => new CalculatorInterceptor()
        ];

        $messagingConfiguration = MessagingSystemConfiguration::createWithCachedReferenceObjectsForNamespaces(
            __DIR__ . "/../../../../",
            ["SimplyCodedSoftware\Messaging", $namespace],
            InMemoryReferenceTypeFromNameResolver::createFromObjects($objects),
            "test",
            true
        );

        $this->messagingSystem = $messagingConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith($objects));
    }

    /**
     * @When I calculate for :amount using gateway
     * @param int $amount
     */
    public function iCalculateForUsingGateway(int $amount)
    {
        /** @var Calculator $gateway */
        $gateway = $this->messagingSystem->getGatewayByName(Calculator::class);

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
        $resultChannel = $this->messagingSystem->getMessageChannelByName("resultChannel");

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
        $gateway = $this->messagingSystem->getGatewayByName(Calculator::class);

        Assert::assertEquals($result, $gateway->calculate($amount));
    }

    /**
     * @When I calculate using inbound channel adapter
     */
    public function iCalculateUsingInboundChannelAdapter()
    {
        $this->messagingSystem->runSeparatelyRunningConsumerBy("inboundCalculator");
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
        $messageChannel = $this->messagingSystem->getMessageChannelByName($channelName);

        Assert::assertEquals($result, $messageChannel->receive()->getPayload());
    }
}
