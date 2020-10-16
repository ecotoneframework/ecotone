<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;

/**
 * @Environment({"prod"})
 */
class ApplicationContextWithClassEnvironment
{
    /**
     * @ApplicationContext()
     */
    public function someAction(): void
    {

    }
}