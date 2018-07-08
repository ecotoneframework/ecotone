<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Fixture\Annotation\ModuleConfiguration\ExampleModuleAndModuleExtensionConfiguration;
use Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Fixture\Annotation\ModuleConfiguration\ExampleModuleConfigurationExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class AnnotationModuleConfigurationRetrievingServiceTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
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
            ExampleModuleConfigurationExtension::class
        ]));

        $this->assertEquals(
            [
                ExampleModuleConfigurationExtension::createEmpty()
            ],
            $annotationModuleRetrievingServie->findAllModuleExtensionConfigurations()
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_registering_separately()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationRegistrationService::createFrom([
            ExampleModuleConfigurationExtension::class, ExampleModuleConfiguration::class
        ]));

        $this->assertEquals(
            [
                ExampleModuleConfiguration::createEmpty()
            ],
            $annotationModuleRetrievingServie->findAllModuleConfigurations()
        );

        $this->assertEquals(
            [
                ExampleModuleConfigurationExtension::createEmpty()
            ],
            $annotationModuleRetrievingServie->findAllModuleExtensionConfigurations()
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_creating_module_and_extension_as_same_instance()
    {
        $annotationModuleRetrievingServie = new AnnotationModuleRetrievingService(InMemoryAnnotationRegistrationService::createFrom([
            ExampleModuleAndModuleExtensionConfiguration::class
        ]));

        $this->assertSame($annotationModuleRetrievingServie->findAllModuleConfigurations(), $annotationModuleRetrievingServie->findAllModuleExtensionConfigurations());
    }
}