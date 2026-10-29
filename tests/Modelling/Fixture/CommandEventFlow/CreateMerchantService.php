<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CommandEventFlow;

use Ecotone\Messaging\Attribute\BusinessMethod;
use Ecotone\Messaging\Attribute\Parameter\Headers;

interface CreateMerchantService
{
    #[BusinessMethod('create.merchant')]
    public function create(CreateMerchant $command, #[Headers] array $metadata): void;
}
