<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptingAggregateUsingAttributes;

use Ecotone\Messaging\Attribute\Parameter\Headers;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Basket
{
    const IS_REGISTRATION = "isRegistration";
    #[AggregateIdentifier]
    private string $userId;
    private array $metadata;

    private function __construct(string $userId, array $data)
    {
        $this->userId     = $userId;
        $this->metadata = $data;
    }

    #[CommandHandler("basket.add")]
    #[AddMetadata(self::IS_REGISTRATION, "true")]
    public static function start(array $command, array $metadata) : self
    {
        return new self($command["userId"], [self::IS_REGISTRATION => $metadata[self::IS_REGISTRATION], "handlerInfo" => $metadata["handlerInfo"]]);
    }

    #[CommandHandler("basket.add")]
    #[AddMetadata(self::IS_REGISTRATION, "false")]
    public function addToBasket(#[Headers] array $metadata) : void
    {
        $this->metadata = [self::IS_REGISTRATION => $metadata[self::IS_REGISTRATION], "handlerInfo" => $metadata["handlerInfo"]];
    }

    #[QueryHandler("basket.get")]
    public function getBasket() : array
    {
        return $this->metadata;
    }
}