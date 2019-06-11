<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Annotation\MediaTypeConverter;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Conversion\ConverterReferenceBuilder;
use SimplyCodedSoftware\Messaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleMediaTypeConverter;

/**
 * Class ConverterModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConverterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_converters()
    {
        $annotationConfiguration = ConverterModule::create(
            InMemoryAnnotationRegistrationService::createEmpty()
                ->registerClassWithAnnotations(ExampleConverterService::class)
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConverter(
                    ReferenceServiceConverterBuilder::create(
                        ExampleConverterService::class,
                        "convert",
                        TypeDescriptor::create("array<string>"),
                        TypeDescriptor::create("array<\stdClass>")
                    )
                )
                ->requireReferences([ExampleConverterService::class]),
            $configuration
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_registering_media_type_converter()
    {
        $annotationConfiguration = ConverterModule::create(
            InMemoryAnnotationRegistrationService::createEmpty()
                ->registerClassWithAnnotations(ExampleMediaTypeConverter::class)
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConverter(
                    ConverterReferenceBuilder::create(ExampleMediaTypeConverter::class)
                )
                ->requireReferences([ExampleMediaTypeConverter::class]),
            $configuration
        );
    }
}