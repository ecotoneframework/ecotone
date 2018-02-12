<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class GatewayAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class GatewayAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $requestChannel;
    /**
     * @var string
     */
    public $replyChannel;
    /**
     * @var array
     */
    public $parameterConverters = [];
}