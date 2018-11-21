<?php
declare(strict_types=1);

namespace Fixture\Annotation\Environment;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Environment;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Extension;

/**
 * Class ApplicationContextWithMethodEnvironments
 * @package Fixture\Annotation\Environment
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