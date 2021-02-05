<?php

namespace Ecotone\Modelling\Attribute;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Config\ConfigurationException;
use Ramsey\Uuid\Uuid;

#[\Attribute(\Attribute::TARGET_METHOD|\Attribute::IS_REPEATABLE)]
class EventHandler extends IdentifiedAnnotation
{
    public string $listenTo;
    public bool $dropMessageOnNotFound;
    public string $outputChannelName;
    public array $requiredInterceptorNames;
    public array $identifierMetadataMapping;

    /**
     * @param string $listenTo Registers event handler to listen from defined inputs | e.g. from single - "ecotone.modelling.created" | e.g. from multiple - "ecotone.modelling.*"
     */
    public function __construct(string $listenTo = "", string $endpointId = "", string $outputChannelName = "", bool $dropMessageOnNotFound = false, array $identifierMetadataMapping = [], array $requiredInterceptorNames = [])
    {
        parent::__construct($endpointId);

        $this->listenTo = $listenTo;
        $this->dropMessageOnNotFound = $dropMessageOnNotFound;
        $this->outputChannelName = $outputChannelName;
        $this->requiredInterceptorNames = $requiredInterceptorNames;
        $this->identifierMetadataMapping = $identifierMetadataMapping;
    }

    public function getListenTo(): string
    {
        return $this->listenTo;
    }

    public function isDropMessageOnNotFound(): bool
    {
        return $this->dropMessageOnNotFound;
    }

    public function getOutputChannelName(): string
    {
        return $this->outputChannelName;
    }

    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }

    public function getIdentifierMetadataMapping(): array
    {
        return $this->identifierMetadataMapping;
    }
}