<?php


namespace TestingNamespace\Correct;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * Class CorrectClass
 * @package TestingNamespace\Correct
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class CorrectClass
{
    /**
     * @return array
     * @Extension()
     */
    public function someExtension() : array
    {
        return [];
    }
}