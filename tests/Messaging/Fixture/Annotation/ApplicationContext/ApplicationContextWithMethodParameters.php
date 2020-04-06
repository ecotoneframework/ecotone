<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * @ApplicationContext()
 */
class ApplicationContextWithMethodParameters
{
    /**
     * @Extension()
     */
    public function someExtension(\stdClass $some)
    {
        return new \stdClass();
    }
}