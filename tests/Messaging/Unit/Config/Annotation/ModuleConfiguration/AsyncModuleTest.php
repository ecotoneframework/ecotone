<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;
use Ecotone\Messaging\Annotation\MediaTypeConverter;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsyncModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConverterModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Conversion\ConverterReferenceBuilder;
use Ecotone\Messaging\Conversion\ReferenceServiceConverterBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncClassExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncMethodExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleConverterService;
use Test\Ecotone\Messaging\Fixture\Annotation\Converter\ExampleMediaTypeConverter;

/**
 * Class ConverterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AsyncModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_async_channel_for_method()
    {
        $annotationConfiguration = AsyncModule::create(
            InMemoryAnnotationRegistrationService::createEmpty()
                ->registerClassWithAnnotations(AsyncMethodExample::class)
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerAsynchronousEndpoint("asyncChannel", "asyncServiceActivator"),
            $configuration
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_registering_async_channel_for_whole_class()
    {
        $annotationConfiguration = AsyncModule::create(
            InMemoryAnnotationRegistrationService::createEmpty()
                ->registerClassWithAnnotations(AsyncClassExample::class)
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerAsynchronousEndpoint("asyncChannel1", "asyncServiceActivator1")
                ->registerAsynchronousEndpoint("asyncChannel2", "asyncServiceActivator2"),
            $configuration
        );
    }
}