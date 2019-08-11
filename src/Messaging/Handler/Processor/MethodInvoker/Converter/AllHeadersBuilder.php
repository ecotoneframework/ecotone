<?php


namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class AllHeadersBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AllHeadersBuilder implements ParameterConverterBuilder
{
    /**
     * @var string
     */
    private $parameterName;

    /**
     * AllHeadersConverter constructor.
     *
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     *
     * @return AllHeadersBuilder
     */
    public static function createWith(string $parameterName) : self
    {
        return new self($parameterName);
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
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ParameterConverter
    {
        return new AllHeadersConverter($this->parameterName);
    }
}