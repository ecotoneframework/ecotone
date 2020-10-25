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

    public function __construct(string $requestChannelName, string $endpointId, array $requiredInterceptorNames)
    {
        parent::__construct($endpointId);

        $this->requestChannelName = $requestChannelName;
        $this->requiredInterceptorNames = $requiredInterceptorNames;
    }

    /**
     * @return string
     */
    public function getRequestChannelName(): string
    {
        return $this->requestChannelName;
    }

    /**
     * @return array
     */
    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }
}