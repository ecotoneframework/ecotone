<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class ReferenceBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ReferenceBuilder implements ParameterConverterBuilder
{
    private string $parameterName;
    private string $referenceServiceName;

    /**
     * ServiceReferenceParameterConverter constructor.
     * @param string $parameterName
     * @param string $referenceName
     */
    private function __construct(string $parameterName, string $referenceName)
    {
        $this->parameterName = $parameterName;
        $this->referenceServiceName = $referenceName;
    }

    /**
     * @param string $parameterName
     * @param string $referenceServiceName
     * @return ReferenceBuilder
     */
    public static function create(string $parameterName, string $referenceServiceName): self
    {
        return new self($parameterName, $referenceServiceName);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, InterfaceToCall $interfaceToCall, InterfaceParameter $interfaceParameter): ParameterConverter
    {
        return new ReferenceConverter(
            $referenceSearchService,
            $this->referenceServiceName
        );
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [$this->referenceServiceName];
    }
}
