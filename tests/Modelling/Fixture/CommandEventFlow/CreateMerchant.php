<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

final class CreateMerchant
{
    public function __construct(public string $merchantId)
    {
    }
}