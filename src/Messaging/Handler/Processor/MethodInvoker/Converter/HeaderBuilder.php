<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InterfaceParameter;
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
    private string $headerName;
    private string $parameterName;
    private bool $isRequired;

    /**
     * HeaderArgument constructor.
     *
     * @param string $parameterName
     * @param string $headerName
     * @param bool   $isRequired
     */
    private function __construct(string $parameterName, string $headerName, bool $isRequired)
    {
        $this->parameterName = $parameterName;
        $this->headerName = $headerName;
        $this->isRequired = $isRequired;
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     *
     * @return HeaderBuilder
     */
    public static function create(string $parameterName, string $headerName) : self
    {
        return new self($parameterName, $headerName, true);
    }

    /**
     * @param string $parameterName
     * @param string $headerName
     * @return HeaderBuilder
     */
    public static function createOptional(string $parameterName, string $headerName) : self
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
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        return HeaderConverter::create(
            $this->parameterName,
            $this->headerName,
            $this->isRequired,
            $referenceSearchService->get(ConversionService::REFERENCE_NAME)
        );
    }
}