<?php

namespace SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class StaticHeaderToMessageAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ParameterToStaticHeaderAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $headerName;
    /**
     * @var string
     * @Required()
     */
    public $headerValue;
}