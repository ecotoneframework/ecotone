<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\ConfigurationException;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextWithConstructorParameters;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextWithMethodParameters;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\StdClassExtensionApplicationContext;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleExtensionObject;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class AnnotationModuleConfigurationRetrievingServiceTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation
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
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            ExampleModuleConfiguration::class
        ]));

        $this->assertEquals(
            [
                ExampleModuleConfiguration::createEmpty()
            ],
            $annotationModuleRetrievingServie->findAllModuleConfigurations()
        );
    }

    public function test_retrieving_application_context()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            StdClassExtensionApplicationContext::class
        ]));

        $this->assertEquals(
            [
                new \stdClass()
            ],
            $annotationModuleRetrievingServie->findAllExtensionObjects()
        );
    }

    public function test_throwing_exception_if_application_context_has_constructor_parameters()
    {
        $this->expectException(ConfigurationException::class);

        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            ApplicationContextWithConstructorParameters::class
        ]));

        $this->assertEquals(
            [
                new \stdClass()
            ],
            $annotationModuleRetrievingServie->findAllExtensionObjects()
        );
    }

    public function test_throwing_exception_if_application_context_has_method_parameters()
    {
        $this->expectException(ConfigurationException::class);

        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            ApplicationContextWithMethodParameters::class
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
    public function test_creating_module_extension()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
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
        $annotationModuleRetrievingService = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
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