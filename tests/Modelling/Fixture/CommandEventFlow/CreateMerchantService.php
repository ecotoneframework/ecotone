<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Api\Attribute\BusinessMethod;
use Ecotone\Api\Attribute\Headers;

interface CreateMerchantService
{
    #[BusinessMethod('create.merchant')]
    public function create(CreateMerchant $command, #[Headers] array $metadata): void;
}
