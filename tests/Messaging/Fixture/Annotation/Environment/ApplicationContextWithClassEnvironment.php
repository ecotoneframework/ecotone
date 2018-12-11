<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Environment;
use SimplyCodedSoftware\Messaging\Annotation\Extension;

/**
 * Class MessageEndpointWithEnvironmentExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Environment
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