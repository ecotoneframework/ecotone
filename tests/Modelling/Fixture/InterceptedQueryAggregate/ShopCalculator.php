<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\WithAggregateEvents;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin\AddFranchise;
use Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange\ExchangeProductForPrice;

#[Aggregate]
class ShopCalculator
{
    use WithAggregateEvents;

    #[AggregateIdentifier]
    private string $shopId;

    private int $margin;

    private function __construct(string $shopId, int $margin)
    {
        $this->shopId = $shopId;
        $this->margin = $margin;
    }

    #[CommandHandler("shop.register")]
    public static function register(array $registerShop): self
    {
        return new self($registerShop["shopId"], $registerShop["margin"]);
    }

    #[QueryHandler("shop.calculatePrice", outputChannelName: "addVat")]
    #[AddFranchise]
    #[ExchangeProductForPrice]
    public function calculatePriceFor(array $query): int
    {
        return $query["productPrice"] + $this->margin;
    }
}