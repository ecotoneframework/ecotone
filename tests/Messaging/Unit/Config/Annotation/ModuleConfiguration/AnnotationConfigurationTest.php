<?php

namespace Ecotone\Tests\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Tests\Messaging\Fixture\Configuration\DumbModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Tests\Messaging\Unit\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Ecotone\Tests\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AnnotationConfigurationTest extends MessagingTest
{
    protected function createAnnotationRegistrationService(string $className, string $methodName, $classAnnotationObject, $methodAnnotationObject): AnnotationFinder
    {
        return InMemoryAnnotationFinder::createEmpty()
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

    protected function createMultipleAnnotationRegistrationService(string $className, string $methodName, array $classAnnotations, array $methodAnnotations): AnnotationFinder
    {
        $inMemoryAnnotationRegistrationService = InMemoryAnnotationFinder::createEmpty();

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
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }
}