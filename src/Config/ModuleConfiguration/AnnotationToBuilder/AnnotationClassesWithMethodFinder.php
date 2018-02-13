<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassLocator;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\ClassMetadataReader;

/**
 * Class AnnotationConfigurationToMessageHandler
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationClassesWithMethodFinder
{
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * AnnotationConfigurationForMessageEndpoint constructor.
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    public function __construct(ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }


    /**
     * @param string $classAnnotationName
     * @param string $methodAnnotationName
     * @return AnnotationRegistration[]
     */
    public function findFor(string $classAnnotationName, string $methodAnnotationName): array
    {
        /** @var MessageEndpoint[] $classesWithAnnotation */
        $classesWithAnnotation = $this->classLocator->getAllClassesWithAnnotation($classAnnotationName);
        $annotationRegistrations = [];

        foreach ($classesWithAnnotation as $classWithAnnotation) {
            $annotationForClass = $this->classMetadataReader->getAnnotationForClass($classWithAnnotation, $classAnnotationName);
            $referenceName = (property_exists($classAnnotationName, 'referenceName') && $annotationForClass->referenceName) ? $annotationForClass->referenceName : $classWithAnnotation;

            $methods = $this->classMetadataReader->getMethodsWithAnnotation($classWithAnnotation, $methodAnnotationName);
            foreach ($methods as $method) {
                $annotation = $this->classMetadataReader->getAnnotationForMethod($classWithAnnotation, $method, $methodAnnotationName);

                $annotationRegistrations[] = new AnnotationRegistration($annotation, $classWithAnnotation, $referenceName, $method);
            }
        }

        return $annotationRegistrations;
    }
}