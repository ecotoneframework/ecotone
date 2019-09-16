<?php


namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;

/**
 * Class AnnotationInterceptorConverter
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptorConverter implements ParameterConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterface;
    /**
     * @var object[]
     */
    private $endpointAnnotations;

    /**
     * AnnotationInterceptorConverter constructor.
     *
     * @param InterfaceToCall $interceptedInterface
     * @param object[] $endpointAnnotations
     */
    public function __construct(InterfaceToCall $interceptedInterface, array $endpointAnnotations)
    {
        $this->interceptedInterface = $interceptedInterface;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceToCall $interfaceToCall, InterfaceParameter $relatedParameter, Message $message, array $endpointAnnotations)
    {
        if ($relatedParameter->canBePassedIn(TypeDescriptor::create(InterfaceToCall::class))) {
            return $this->interceptedInterface;
        }

        foreach ($this->endpointAnnotations as $endpointAnnotation) {
            if ($relatedParameter->canBePassedIn(TypeDescriptor::createFromVariable($endpointAnnotation))) {
                return $endpointAnnotation;
            }
        }

        if ($this->interceptedInterface->hasMethodAnnotation($relatedParameter->getTypeDescriptor())) {
            return $this->interceptedInterface->getMethodAnnotation($relatedParameter->getTypeDescriptor());
        }

        return $this->interceptedInterface->getClassAnnotation($relatedParameter->getTypeDescriptor());
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return
            $parameter->canBePassedIn(TypeDescriptor::create(InterfaceToCall::class))
            || $this->interceptedInterface->hasMethodAnnotation($parameter->getTypeDescriptor())
            || $this->interceptedInterface->hasClassAnnotation($parameter->getTypeDescriptor());
    }
}