<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;

/**
 * Class AnnotationConfigurationToMessageHandler
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
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
        /** @var MessageEndpointAnnotation[] $classesWithAnnotation */
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