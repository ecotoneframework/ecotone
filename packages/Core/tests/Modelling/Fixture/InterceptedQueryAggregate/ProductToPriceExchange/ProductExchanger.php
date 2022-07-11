<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange;

use Ecotone\Messaging\Attribute\Interceptor\Before;

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
