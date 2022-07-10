<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\InMemoryConfigurationVariableService;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextWithConstructorParameters;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\ApplicationContextWithMethodParameters;
use Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext\StdClassExtensionApplicationContext;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleExtensionObject;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleRetrievingService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleExtensionWithVariableConfiguration;
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
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::createEmpty());

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
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::createEmpty());

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
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::createEmpty());

        $annotationModuleRetrievingServie->findAllExtensionObjects();
    }

    public function test_creating_module_extension()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            ExampleModuleExtensionObject::class
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::createEmpty());

        $this->assertEquals(
            [
                new \stdClass()
            ],
            $annotationModuleRetrievingServie->findAllExtensionObjects()
        );
    }

    public function test_creating_with_variable_configuration_service()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationFinder::createFrom([
            ExampleModuleExtensionWithVariableConfiguration::class
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::create(["name" => "johny", "lastName" => "bravo"]));

        $stdClass = new \stdClass();
        $stdClass->name = "johny";
        $stdClass->surname = "bravo";

        $this->assertEquals(
            [$stdClass],
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
        ]), InterfaceToCallRegistry::createEmpty(), InMemoryConfigurationVariableService::createEmpty());

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