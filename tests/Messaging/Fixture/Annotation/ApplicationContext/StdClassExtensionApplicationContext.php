<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * @ApplicationContext()
 */
class StdClassExtensionApplicationContext
{
    /**
     * @Extension()
     */
    public function someExtension()
    {
        return new \stdClass();
    }
}