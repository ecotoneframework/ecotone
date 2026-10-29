<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Messaging\Attribute\Converter;

final class MerchantConversion
{
    #[Converter]
    public function convertFromCreateMerchant(CreateMerchant $command): array
    {
        return [
            'merchantId' => $command->merchantId,
        ];
    }

    #[Converter]
    public function convertToCreateMerchant(array $command): CreateMerchant
    {
        return new CreateMerchant($command['merchantId']);
    }
}
