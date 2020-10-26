<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\ProductToPriceExchange;

use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

class ProductExchanger
{
    const MILK = "milk";
    const MILK_PRICE = 100;

    #[Before(pointcut: ExchangeProductForPrice::class)]
    public function exchange(array $query) : array
    {
        return [
            "shopId" => $query["shopId"],
            "productPrice" => $query["productType"] === self::MILK ? self::MILK_PRICE : 0
        ];
    }
}