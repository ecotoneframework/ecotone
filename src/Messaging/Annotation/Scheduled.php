<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Annotation;

/**
 * Class InboundChannelAdapter
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Scheduled extends ChannelAdapter
{
    /**
     * @Required()
     */
    public string $requestChannelName;
    /**
     * Required interceptor reference names
     */
    public array $requiredInterceptorNames = [];
}