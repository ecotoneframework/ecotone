<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class AnnotationInterceptorConverterBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorConverterBuilder implements ParameterConverterBuilder
{
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterface;
    /**
     * @var array|object[]
     */
    private $endpointAnnotations;

    /**
     * AnnotationInterceptorConverterBuilder constructor.
     *
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     */
    private function __construct(InterfaceToCall $interceptedInterface, array $endpointAnnotations)
    {
        $this->interceptedInterface = $interceptedInterface;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    /**
     * @param InterfaceToCall $interfaceToCall
     * @param object[] $endpointAnnotations
     * @return InterceptorConverterBuilder
     */
    public static function create(InterfaceToCall $interfaceToCall, array $endpointAnnotations) : self
    {
        return new self($interfaceToCall, $endpointAnnotations);
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
        return new InterceptorConverter($this->interceptedInterface, $this->endpointAnnotations);
    }
}