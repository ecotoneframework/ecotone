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