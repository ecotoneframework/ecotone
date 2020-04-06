<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * @ApplicationContext()
 */
class ApplicationContextWithConstructorParameters
{
    public function __construct(\stdClass $some)
    {
    }

    /**
     * @Extension()
     */
    public function someExtension()
    {
        return new \stdClass();
    }
}