<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\QueryBus;

class SomeQueryHandler
{
    const SUM = "sum";
    const MULTIPLY = "multiply";
    const SUM_AND_MULTIPLY = "sumAndMultiply";
    const CALCULATE = "calculate";

    #[QueryHandler(SomeQueryHandler::CALCULATE)]
    public function calculate(Message $message, MessageBasedQueryBusExample $queryBus) : Message
    {
        return $this->callQueryBus(self::SUM_AND_MULTIPLY, $queryBus, $message);
    }

    #[QueryHandler(SomeQueryHandler::SUM)]
    public function sum(Message $message) : Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() + 1)
            ->build();
    }

    #[QueryHandler(SomeQueryHandler::MULTIPLY)]
    public function multiply(Message $message) : Message
    {
        return MessageBuilder::fromMessage($message)
            ->setPayload($message->getPayload() * 2)
            ->build();
    }

    #[QueryHandler(SomeQueryHandler::SUM_AND_MULTIPLY)]
    public function sumAndMultiply(Message $message, MessageBasedQueryBusExample $queryBus) : Message
    {
        return $this->callQueryBus(self::MULTIPLY, $queryBus, $this->callQueryBus(self::SUM, $queryBus, $message));
    }

    private function callQueryBus(string $action, MessageBasedQueryBusExample $queryBus, Message $sum) : Message
    {
        return $queryBus->convertAndSend($action, MediaType::APPLICATION_X_PHP, $sum);
    }
}