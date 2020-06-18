<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;
use Ecotone\Messaging\Annotation\Extension;

/**
 * @ApplicationContext()
 */
class ApplicationContextWithMethodMultipleEnvironmentsExample
{
    /**
     * @return array
     * @Extension()
     * @Environment({"dev", "prod", "test"})
     */
    public function configMultipleEnvironments() : array
    {
        return [];
    }
}