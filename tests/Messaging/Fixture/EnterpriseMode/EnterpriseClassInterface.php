<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\EnterpriseMode;

use Ecotone\Messaging\Attribute\Enterprise;

#[Enterprise]
class EnterpriseClassInterface
{
    public function execute(): void
    {

    }
}
