<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\EnterpriseMode;

use Ecotone\Messaging\Attribute\Enterprise;

/**
 * licence Apache-2.0
 */
class EnterpriseMethodInterface
{
    #[Enterprise]
    public function execute(): void
    {

    }
}
