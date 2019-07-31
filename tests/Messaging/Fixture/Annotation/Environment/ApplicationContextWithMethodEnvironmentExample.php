<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;
use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;
use Ecotone\Messaging\Annotation\Extension;

/**
 * Class ApplicationContextWithMethodEnvironments
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Environment
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 * @Environment({"prod", "dev"})
 */
class ApplicationContextWithMethodEnvironmentExample
{
    /**
     * @return array
     * @Extension()
     * @Environment({"dev"})
     */
    public function configSingleEnvironment() : array
    {
        return [];
    }
}