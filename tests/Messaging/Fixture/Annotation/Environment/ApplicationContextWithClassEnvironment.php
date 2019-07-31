<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\Environment;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Environment;
use Ecotone\Messaging\Annotation\Extension;

/**
 * Class MessageEndpointWithEnvironmentExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Environment
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