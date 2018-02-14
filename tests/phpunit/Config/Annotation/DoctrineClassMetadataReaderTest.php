<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\DoctrineClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class DoctrineClassMetadataReaderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DoctrineClassMetadataReaderTest extends MessagingTest
{
    public function test_retrieving_all_methods_with_annotation()
    {
        $this->assertEquals(
            ["sendMessage"],
            $this->createClassMetadataReader()->getMethodsWithAnnotation(ServiceActivatorWithAllConfigurationDefined::class, ServiceActivatorAnnotation::class)
        );
    }

    public function test_returning_empty_if_no_annotations_found()
    {
        $this->assertEquals(
            [],
            $this->createClassMetadataReader()->getMethodsWithAnnotation(ServiceActivatorWithAllConfigurationDefined::class, ModuleConfigurationAnnotation::class)
        );
    }

    public function test_returning_annotation_from_method()
    {
        $this->assertInstanceOf(
            ServiceActivatorAnnotation::class,
            $this->createClassMetadataReader()->getAnnotationForMethod(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage", ServiceActivatorAnnotation::class)
        );
    }

    public function test_throwing_exception_if_no_method_with_annotation_found()
    {
        $this->expectException(ConfigurationException::class);

        $this->createClassMetadataReader()->getAnnotationForMethod(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage", ModuleConfigurationAnnotation::class);
    }

    public function test_throwing_exception_if_no_method_found()
    {
        $this->expectException(ConfigurationException::class);

        $this->createClassMetadataReader()->getAnnotationForMethod(ServiceActivatorWithAllConfigurationDefined::class, "doesnotexists", ServiceActivatorAnnotation::class);
    }

    public function test_returning_annotation_from_class()
    {
        $this->assertInstanceOf(
            MessageEndpointAnnotation::class,
            $this->createClassMetadataReader()->getAnnotationForClass(ServiceActivatorWithAllConfigurationDefined::class, MessageEndpointAnnotation::class)
        );
    }

    public function test_throwing_exception_if_annotation_not_found()
    {
        $this->expectException(ConfigurationException::class);

        $this->createClassMetadataReader()->getAnnotationForClass(ServiceActivatorWithAllConfigurationDefined::class, ModuleConfigurationAnnotation::class);
    }

    public function test_throwing_exception_if_class_not_found()
    {
        $this->expectException(ConfigurationException::class);

        $this->createClassMetadataReader()->getAnnotationForClass("some", ModuleConfigurationAnnotation::class);
    }

    /**
     * @return DoctrineClassMetadataReader
     */
    private function createClassMetadataReader(): DoctrineClassMetadataReader
    {
        $metadataReader = new DoctrineClassMetadataReader(new AnnotationReader());
        return $metadataReader;
    }
}