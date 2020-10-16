<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;

/**
 * @Environment({"prod", "dev"})
 */
class ApplicationContextWithMethodEnvironmentExample
{
    /**
     * @ApplicationContext()
     * @Environment({"dev"})
     */
    public function configSingleEnvironment() : array
    {
        return [];
    }
}