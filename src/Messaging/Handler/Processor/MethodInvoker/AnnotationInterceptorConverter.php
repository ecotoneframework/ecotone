<?php


namespace SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker;

use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\ParameterConverter;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class AnnotationInterceptorConverter
 * @package SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationInterceptorConverter implements ParameterConverter
{
    /**
     * @var InterfaceToCall
     */
    private $interceptedInterface;

    /**
     * AnnotationInterceptorConverter constructor.
     *
     * @param InterfaceToCall $interceptedInterface
     */
    public function __construct(InterfaceToCall $interceptedInterface)
    {
        $this->interceptedInterface = $interceptedInterface;
    }

    /**
     * @inheritDoc
     */
    public function getArgumentFrom(InterfaceParameter $relatedParameter, Message $message)
    {
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
        return $this->interceptedInterface->hasMethodAnnotation($parameter->getTypeDescriptor()) || $this->interceptedInterface->hasClassAnnotation($parameter->getTypeDescriptor());
    }
}