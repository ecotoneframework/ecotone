<?php


namespace Incorrect\TestingNamespace;


use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * Class CorrectNamespace
 * @package Incorrect\TestingNamespace
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class CorrectNamespace
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