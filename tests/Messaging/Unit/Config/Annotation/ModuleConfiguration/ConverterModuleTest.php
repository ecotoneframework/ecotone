<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\MediaTypeConverter;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleMediaTypeConverter;

/**
 * Class ConverterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConverterModuleTest extends AnnotationConfigurationTest
{
    public function test_registering_converters()
    {
        $annotationConfiguration = ConverterModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleConverterService::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConverter(
                    ReferenceServiceConverterBuilder::create(
                        "exampleConverterService",
                        "convert",
                        TypeDescriptor::create("array<string>"),
                        TypeDescriptor::create("array<\stdClass>")
                    )
                )
                ->requireReferences(["exampleConverterService"]),
            $configuration
        );
    }

    public function test_registering_media_type_converter()
    {
        $annotationConfiguration = ConverterModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleMediaTypeConverter::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

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