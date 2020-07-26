<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Messaging\Message;

class ErrorReceiver
{
    /**
     * @var string|null
     */
    private $errorOrder;

    /**
     * @ServiceActivator(inputChannelName=ErrorConfigurationContext::DEAD_LETTER_CHANNEL)
     */
    public function receiveError(Message $message) : void
    {
        $this->errorOrder = $message->getPayload();
    }

    /**
     * @ServiceActivator(inputChannelName="getErrorMessage")
     */
    public function getErrorOrder() : ?string
    {
        return $this->errorOrder ? $this->errorOrder : null;
    }
}