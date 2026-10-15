<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange;

use Ecotone\Api\Attribute\Before;

/**
 * licence Apache-2.0
 */
class ProductExchanger
{
    public const MILK = 'milk';
    public const MILK_PRICE = 100;

    #[Before(pointcut: ExchangeProductForPrice::class)]
    public function exchange(array $query): array
    {
        return [
            'shopId' => $query['shopId'],
            'productPrice' => $query['productType'] === self::MILK ? self::MILK_PRICE : 0,
        ];
    }
}
