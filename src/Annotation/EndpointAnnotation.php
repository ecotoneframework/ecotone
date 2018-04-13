<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

/**
 * Class EndpointAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class EndpointAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;
}