<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
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
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ConverterModuleTestCase extends AnnotationConfigurationTestCase
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
                        'exampleConverterService',
                        'convert',
                        TypeDescriptor::create('array<string>'),
                        TypeDescriptor::create("array<\stdClass>")
                    )
                ),
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
                ),
            $configuration
        );
    }
}
