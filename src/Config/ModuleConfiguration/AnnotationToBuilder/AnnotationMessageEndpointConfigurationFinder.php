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
class AnnotationMessageEndpointConfigurationFinder
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
     * @param string $messageHandlerAnnotationName
     * @return AnnotationRegistration[]
     */
    public function findFor(string $messageHandlerAnnotationName): array
    {
        /** @var MessageEndpoint[] $messageEndpoints */
        $messageEndpoints = $this->classLocator->getAllClassesWithAnnotation(MessageEndpoint::class);
        $annotationRegistrations = [];

        foreach ($messageEndpoints as $messageEndpoint) {
            /** @var MessageEndpoint $messageEndpointAnnotation */
            $messageEndpointAnnotation = $this->classMetadataReader->getAnnotationForClass($messageEndpoint, MessageEndpoint::class);
            $referenceName = $messageEndpointAnnotation->referenceName ? $messageEndpointAnnotation->referenceName : $messageEndpoint;

            $methods = $this->classMetadataReader->getMethodsWithAnnotation($messageEndpoint, $messageHandlerAnnotationName);
            foreach ($methods as $method) {
                $annotation = $this->classMetadataReader->getAnnotationForMethod($messageEndpoint, $method, $messageHandlerAnnotationName);

                $annotationRegistrations[] = new AnnotationRegistration($annotation, $messageEndpoint, $referenceName, $method);
            }
        }

        return $annotationRegistrations;
    }
}