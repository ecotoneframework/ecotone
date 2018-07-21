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
     * @Required()
     */
    public $inputChannelName;

    /**
     * List of method level pre interceptors
     *
     * @var array
     */
    public $preCallInterceptors = [];
    /**
     * List of method level post interceptors
     *
     * @var array
     */
    public $postCallInterceptors = [];
}