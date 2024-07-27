<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\EnterpriseMode;

use Ecotone\Messaging\Attribute\Enterprise;

class EnterpriseMethodInterface
{
    #[Enterprise]
    public function execute(): void
    {

    }
}
