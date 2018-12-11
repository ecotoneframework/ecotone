<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Conversion\ReferenceServiceConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Converter\ExampleConverterService;

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
        $annotationConfiguration->prepare($configuration, [], ConfigurableReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerConverter(
                    ReferenceServiceConverterBuilder::create(
                        ExampleConverterService::class,
                        "convert",
                        TypeDescriptor::create("array<string>"),
                        TypeDescriptor::create("array<\stdClass>")
                    )
                ),
            $configuration
        );
    }
}