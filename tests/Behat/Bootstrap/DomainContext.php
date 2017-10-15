<?php

namespace Behat\Bootstrap;

use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Messaging\Channel\DirectChannel;

/**
 * Defines application features from the specific context.
 */
class DomainContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    private $service;


    /**
     * @Given I activate service :arg1 with method :arg2 to listen on :arg3 channel
     * @param string $serviceName
     * @param string $methodName
     * @param string $channelName
     */
    public function iActivateServiceWithMethodToListenOnChannel(string $serviceName, string $methodName, string $channelName)
    {
        $directChannel = DirectChannel::create();
        $directChannel->subscribe();
    }

    /**
     * @When message with payload :arg1 comes to :arg2 channel
     */
    public function messageWithPayloadComesToChannel($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @Then booking request should be processed
     */
    public function bookingRequestShouldBeProcessed()
    {
        throw new PendingException();
    }
}
