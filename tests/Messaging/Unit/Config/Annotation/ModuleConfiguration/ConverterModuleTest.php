<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
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
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
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