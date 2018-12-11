<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Config\Annotation;

use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class AnnotationRegistration
 * @package SimplyCodedSoftware\Messaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationRegistration
{
    /**
     * @var object
     */
    private $annotationForClass;
    /**
     * Annotation to register
     *
     * @var object
     */
    private $annotationForMethod;
    /**
     * Message endpoint class containing the annotation
     *
     * @var string
     */
    private $className;
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
     * @param object $annotationForClass
     * @param object $annotationForMethod
     * @param string $className
     * @param string $methodName
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __construct($annotationForClass, $annotationForMethod, string $className, string $methodName)
    {
        Assert::isObject($annotationForClass, "Annotation for class should be object");
        Assert::isObject($annotationForMethod, "Found annotation should be object");

        $this->annotationForClass = $annotationForClass;
        $this->annotationForMethod = $annotationForMethod;
        $this->className = $className;
        $this->methodName = $methodName;

        $this->initialize($annotationForClass, $className);
    }

    /**
     * @param $annotationForClass
     * @param $annotationForMethod
     * @param string $className
     * @param string $methodName
     * @return AnnotationRegistration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function create($annotationForClass, $annotationForMethod, string $className, string $methodName) : self
    {
        return new self($annotationForClass, $annotationForMethod, $className, $methodName);
    }

    /**
     * @return object
     */
    public function getAnnotationForClass()
    {
        return $this->annotationForClass;
    }

    /**
     * @return object
     */
    public function getAnnotationForMethod()
    {
        return $this->annotationForMethod;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
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

    /**
     * @param object $annotationForClass
     * @param string $classNameWithAnnotation
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize($annotationForClass, string $classNameWithAnnotation) : void
    {
        Assert::isObject($annotationForClass, "Class for annotation must be object");

        $this->referenceName = (property_exists($annotationForClass, 'referenceName') && $annotationForClass->referenceName) ? $annotationForClass->referenceName : $classNameWithAnnotation;
    }
}