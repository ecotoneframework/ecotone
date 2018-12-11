<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment;
use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Environment;
use SimplyCodedSoftware\Messaging\Annotation\Extension;

/**
 * Class ApplicationContextWithMethodEnvironments
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment
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