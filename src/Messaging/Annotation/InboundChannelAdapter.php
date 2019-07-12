<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Annotation;

/**
 * Class InboundChannelAdapter
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class InboundChannelAdapter extends EndpointAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $requestChannelName;
    /**
     * Required interceptor reference names
     *
     * @var array
     */
    public $requiredInterceptorNames = [];
}