<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\QueryBus;

/**
 * @MessageEndpoint()
 */
class SomeQueryHandler
{
    const SUM = "sum";
    const MULTIPLY = "multiply";
    const SUM_AND_MULTIPLY = "sumAndMultiply";
    const CALCULATE = "calculate";

    /**
     * @QueryHandler(inputChannelName=SomeQueryHandler::CALCULATE)
     */
    public function calculate(Message $message, MessageBasedQueryBusExample $queryBus) : Message
    {
        return $this->callQueryBus(self::SUM_AND_MULTIPLY, $queryBus, $message);
    }

    /**
     * @QueryHandler(inputChannelName=SomeQueryHandler::SUM)
     */
    public function sum(Message $message) : Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() + 1)
            ->build();
    }

    /**
     * @QueryHandler(inputChannelName=SomeQueryHandler::MULTIPLY)
     */
    public function multiply(Message $message) : Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() * 2)
            ->build();
    }

    /**
     * @QueryHandler(inputChannelName=SomeQueryHandler::SUM_AND_MULTIPLY)
     */
    public function sumAndMultiply(Message $message, MessageBasedQueryBusExample $queryBus) : Message
    {
        return $this->callQueryBus(self::MULTIPLY, $queryBus, $this->callQueryBus(self::SUM, $queryBus, $message));
    }

    private function callQueryBus(string $action, MessageBasedQueryBusExample $queryBus, Message $sum) : Message
    {
        return $queryBus->convertAndSend($action, MediaType::APPLICATION_X_PHP, $sum);
    }
}