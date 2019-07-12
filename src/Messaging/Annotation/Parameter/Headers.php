<?php


namespace SimplyCodedSoftware\Messaging\Annotation\Parameter;

/**
 * Class AllHeaders
 * @package SimplyCodedSoftware\Messaging\Annotation\Parameter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
class Headers
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}