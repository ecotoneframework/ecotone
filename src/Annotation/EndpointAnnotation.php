<?php
declare(strict_types=1);

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
     */
    public $endpointId = "";
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;
}