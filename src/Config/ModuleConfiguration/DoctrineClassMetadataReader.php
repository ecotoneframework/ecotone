<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationReader;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;

/**
 * Class DoctrineClassMetadataReader
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoctrineClassMetadataReader implements ClassMetadataReader
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * DoctrineClassMetadataReader constructor.
     * @param AnnotationReader $annotationReader
     */
    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @inheritDoc
     */
    public function getMethodsWithAnnotation(string $className, string $annotationName): array
    {
        $methodsWithAnnotation = [];

        foreach (get_class_methods($className) as $method) {
            if ($this->getMethodAnnotation($className, $method, $annotationName)) {
                $methodsWithAnnotation[] = $method;
            }
        }

        return $methodsWithAnnotation;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationForMethod(string $className, string $methodName, string $annotationName)
    {
        $annotation = $this->getMethodAnnotation($className, $methodName, $annotationName);

        if (!$annotation) {
            throw ConfigurationException::create("{$annotationName} for {$className} and method {$methodName} does not exists");
        }

        return $annotation;
    }

    /**
     * @inheritDoc
     */
    public function getAnnotationForClass(string $className, string $annotationName)
    {
        try {
            $annotation = $this->annotationReader->getClassAnnotation(new \ReflectionClass($className), $annotationName);

            if (!$annotation) {
                throw ConfigurationException::create("{$annotationName} for {$className} does not exists");
            }

            return $annotation;
        }catch (\ReflectionException $e) {
            throw ConfigurationException::create("{$annotationName} for {$className} does not exists");
        }
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param string $annotationName
     * @return null|object
     * @throws ConfigurationException
     */
    private function getMethodAnnotation(string $className, string $methodName, string $annotationName)
    {
        try {
            $reflectionMethod = new \ReflectionMethod($className, $methodName);

            return $this->annotationReader->getMethodAnnotation($reflectionMethod, $annotationName);
        }catch (\ReflectionException $e) {
            throw ConfigurationException::create("Class {$className} with method {$methodName} and annotation {$annotationName} does not exists");
        }
    }
}