<?php
declare(strict_types=1);

namespace Fixture\Annotation\Environment;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Environment;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Extension;

/**
 * Class MessageEndpointWithEnvironmentExample
 * @package Fixture\Annotation\Environment
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 * @Environment({"prod"})
 */
class ApplicationContextWithClassEnvironment
{
    /**
     * @Extension()
     */
    public function someAction() : void
    {

    }
}