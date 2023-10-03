<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ValueBuilder implements ParameterConverterBuilder
{
    public function __construct(private string $parameterName, private mixed $staticValue)
    {
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
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService, InterfaceToCall $interfaceToCall, InterfaceParameter $interfaceParameter): ParameterConverter
    {
        return ValueConverter::createWith($this->staticValue);
    }
}
