<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Annotation;

/**
 * Class InboundChannelAdapter
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class InboundChannelAdapter extends ChannelAdapter
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