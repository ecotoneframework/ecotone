<?php

namespace Messaging\Dsl;

use Messaging\MessageChannel;
use Messaging\Support\Assert;

/**
 * Class IntegrationFlow
 * @package Messaging\Dsl
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class IntegrationFlow
{
    /**
     * @var MessageChannel
     */
    private $messageChannel;

    /**
     * IntegrationFlow constructor.
     * @param MessageChannel $messageChannel
     */
    private function __construct(MessageChannel $messageChannel)
    {
        $this->messageChannel = $messageChannel;
    }

    /**
     * @param MessageChannel $messageChannel
     * @return IntegrationFlow
     */
    public static function from(MessageChannel $messageChannel) : self
    {
        return new self($messageChannel);
    }

    public function handle($serviceToBeActivated, string $methodToBeCalled) : self
    {
        Assert::isObject($serviceToBeActivated, "Service to be activated must be object");
        Assert::notNullAndEmpty($methodToBeCalled, "Method to be called can't be empty");

        
    }
}