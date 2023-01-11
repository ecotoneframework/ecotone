<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

final class MerchantCreated
{
    public function __construct(public string $merchantId)
    {
    }
}
