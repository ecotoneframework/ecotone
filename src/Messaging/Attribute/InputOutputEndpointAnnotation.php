<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

/**
 * licence Apache-2.0
 */
abstract class InputOutputEndpointAnnotation extends EndpointAnnotation
{
    private string $outputChannelName;
    private array $requiredInterceptorNames;

    public function __construct(string $inputChannelName = '', string $endpointId = '', string $outputChannelName = '', array $requiredInterceptorNames = [])
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
