<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;

/**
 * Class AnnotationInterceptorConverterBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorConverterBuilder implements ParameterConverterBuilder
{
    private \Ecotone\Messaging\Handler\InterfaceToCall $interceptedInterface;
    private array $endpointAnnotations;
    private string $parameterName;

    /**
     * AnnotationInterceptorConverterBuilder constructor.
     *
     * @param string $parameterName
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     */
    private function __construct(string $parameterName, InterfaceToCall $interceptedInterface, array $endpointAnnotations)
    {
        $this->parameterName = $parameterName;
        $this->interceptedInterface = $interceptedInterface;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    /**
     * @param string $parameterName
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     * @return InterceptorConverterBuilder
     */
    public static function create(string $parameterName, InterfaceToCall $interceptedInterface, array $endpointAnnotations) : self
    {
        return new self($parameterName, $interceptedInterface, $endpointAnnotations);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $this->parameterName === $parameter->getName();
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
        return new InterceptorConverter($this->parameterName, $this->interceptedInterface, $this->endpointAnnotations);
    }
}