<?php

namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class AnnotationInterceptorConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationInterceptorConverterBuilder implements ParameterConverterBuilder
{
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterface;

    /**
     * AnnotationInterceptorConverterBuilder constructor.
     *
     * @param InterfaceToCall $interceptedInterface
     */
    private function __construct(InterfaceToCall $interceptedInterface)
    {
        $this->interceptedInterface = $interceptedInterface;
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

    }
}