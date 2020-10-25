<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

abstract class InputOutputEndpointAnnotation extends EndpointAnnotation
{
    public string $outputChannelName;
    public array $requiredInterceptorNames;

    public function __construct(string $inputChannelName = "", string $endpointId = "", string $outputChannelName = "", array $requiredInterceptorNames = [])
    {
        parent::__construct($inputChannelName, $endpointId);

        $this->outputChannelName = $outputChannelName;
        $this->requiredInterceptorNames = $requiredInterceptorNames;
    }

    public function getOutputChannelName(): string
    {
        return $this->outputChannelName;
    }

    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }
}