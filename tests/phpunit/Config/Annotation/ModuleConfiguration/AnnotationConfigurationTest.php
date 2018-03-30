<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Configuration\DumbModuleRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\FileSystemClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class AnnotationConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
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
     * @return MessagingSystemConfiguration
     */
    protected function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(DumbModuleRetrievingService::createEmpty());
    }
}