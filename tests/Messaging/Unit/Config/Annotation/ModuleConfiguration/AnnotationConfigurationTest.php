<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Fixture\Configuration\DumbModuleRetrievingService;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AnnotationConfigurationTest extends MessagingTest
{
    /**
     * @param string $className
     * @param string $methodName
     * @param $classAnnotationObject
     * @param $methodAnnotationObject
     * @return AnnotationRegistrationService
     */
    protected function createAnnotationRegistrationService(string $className, string $methodName, $classAnnotationObject, $methodAnnotationObject): AnnotationRegistrationService
    {
        return InMemoryAnnotationRegistrationService::createEmpty()
            ->addAnnotationToClass(
                $className,
                $classAnnotationObject
            )
            ->addAnnotationToClassMethod(
                $className,
                $methodName,
                $methodAnnotationObject
            );
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param array $classAnnotations
     * @param array $methodAnnotations
     * @return AnnotationRegistrationService
     */
    protected function createMultipleAnnotationRegistrationService(string $className, string $methodName, array $classAnnotations, array $methodAnnotations): AnnotationRegistrationService
    {
        $inMemoryAnnotationRegistrationService = InMemoryAnnotationRegistrationService::createEmpty();

        foreach ($classAnnotations as $classAnnotation) {
            $inMemoryAnnotationRegistrationService
                ->addAnnotationToClass($className, $classAnnotation);
        }
        foreach ($methodAnnotations as $methodAnnotation) {
            $inMemoryAnnotationRegistrationService
                ->addAnnotationToClassMethod($className, $methodName, $methodAnnotation);
        }

        return $inMemoryAnnotationRegistrationService;
    }

    /**
     * @return MessagingSystemConfiguration
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }
}