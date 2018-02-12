<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class AnnotationRegistration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationRegistration
{
    /**
     * Annotation to register
     *
     * @var object
     */
    private $annotation;
    /**
     * Message endpoint class containing the annotation
     *
     * @var string
     */
    private $messageEndpointClass;
    /**
     * Reference name to object
     *
     * @var string
     */
    private $referenceName;
    /**
     * @var string
     */
    private $methodName;

    /**
     * AnnotationRegistration constructor.
     * @param object $annotation
     * @param string $messageEndpointClass
     * @param string $referenceName
     * @param string $methodName
     */
    public function __construct($annotation, string $messageEndpointClass, string $referenceName, string $methodName)
    {
        Assert::isObject($annotation, "Found annotation should be object");

        $this->annotation = $annotation;
        $this->messageEndpointClass = $messageEndpointClass;
        $this->referenceName = $referenceName;
        $this->methodName = $methodName;
    }

    /**
     * @return object
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @return string
     */
    public function getMessageEndpointClass(): string
    {
        return $this->messageEndpointClass;
    }

    /**
     * @return string
     */
    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }
}