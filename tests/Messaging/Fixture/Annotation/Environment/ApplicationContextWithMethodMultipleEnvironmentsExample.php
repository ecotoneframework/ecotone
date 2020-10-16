<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;

class ApplicationContextWithMethodMultipleEnvironmentsExample
{
    /**
     * @return array
     * @ApplicationContext()
     * @Environment({"dev", "prod", "test"})
     */
    public function configMultipleEnvironments() : array
    {
        return [];
    }
}