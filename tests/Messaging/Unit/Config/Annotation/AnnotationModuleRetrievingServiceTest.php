<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation;

use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleExtensionObject;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class AnnotationModuleConfigurationRetrievingServiceTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationModuleRetrievingServiceTest extends MessagingTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_creating_module()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationRegistrationService::createFrom([
            ExampleModuleConfiguration::class
        ]));

        $this->assertEquals(
            [
                ExampleModuleConfiguration::createEmpty()
            ],
            $annotationModuleRetrievingServie->findAllModuleConfigurations()
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_creating_module_extension()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationRegistrationService::createFrom([
            ExampleModuleExtensionObject::class
        ]));

        $this->assertEquals(
            [
                new \stdClass()
            ],
            $annotationModuleRetrievingServie->findAllExtensionObjects()
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_registering_separately()
    {
        $annotationModuleRetrievingService = new AnnotationModuleRetrievingService(InMemoryAnnotationRegistrationService::createFrom([
            ExampleModuleExtensionObject::class, ExampleModuleConfiguration::class
        ]));

        $this->assertEquals(
            [
                ExampleModuleConfiguration::createEmpty()
            ],
            $annotationModuleRetrievingService->findAllModuleConfigurations()
        );

        $this->assertEquals(
            [
                new \stdClass()
            ],
            $annotationModuleRetrievingService->findAllExtensionObjects()
        );
    }
}