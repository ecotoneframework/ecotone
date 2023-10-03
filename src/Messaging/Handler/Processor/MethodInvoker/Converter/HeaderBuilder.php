<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class HeaderBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class HeaderBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName, private string $headerName, private bool $isRequired)
    {
    }

    public static function create(string $parameterName, string $headerName): self
    {
        return new self($parameterName, $headerName, true);
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return HeaderBuilder
     */
    public static function createOptional(string $parameterName, string $headerName): self
    {
        return new self($parameterName, $headerName, false);
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
        return new HeaderConverter(
            $interfaceParameter,
            $this->headerName,
            $this->isRequired,
            $referenceSearchService->get(ConversionService::REFERENCE_NAME),
        );
    }
}
