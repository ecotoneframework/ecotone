<?php

namespace Test\Ecotone\JMSConverter\Unit;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\JMSConverter\Configuration\JMSConverterConfigurationModule;
use Ecotone\JMSConverter\JMSConverterBuilder;
use Ecotone\JMSConverter\JMSConverterConfiguration;
use Ecotone\JMSConverter\JMSHandlerAdapter;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDescriptor;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\JMSConverter\Fixture\Configuration\ArrayConversion\ArrayToArrayConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\ArrayConversion\ClassToArrayConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\ClassToClass\ClassToClassConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\SimpleTypeToSimpleType\SimpleTypeToSimpleType;
use Test\Ecotone\JMSConverter\Fixture\Configuration\Status\Status;
use Test\Ecotone\JMSConverter\Fixture\Configuration\Status\StatusConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter\AppointmentType;
use Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter\AppointmentTypeConverter;
use Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter\StandardAppointmentType;
use Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter\TrialAppointmentType;

/**
 * @internal
 */
class JMSConverterConfigurationTest extends TestCase
{
    public function test_registering_converter_and_convert()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([StatusConverter::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(
                    new JMSConverterBuilder(
                        [
                            JMSHandlerAdapter::create(
                                TypeDescriptor::create(Status::class),
                                TypeDescriptor::createStringType(),
                                StatusConverter::class,
                                'convertFrom'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::createStringType(),
                                TypeDescriptor::create(Status::class),
                                StatusConverter::class,
                                'convertTo'
                            ),
                        ],
                        JMSConverterConfiguration::createWithDefaults(),
                        null
                    )
                ),
            $configuration,
        );
    }

    public function test_register_union_converter()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([AppointmentTypeConverter::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(
                    new JMSConverterBuilder(
                        [
                            JMSHandlerAdapter::create(
                                TypeDescriptor::create(AppointmentType::class),
                                TypeDescriptor::createStringType(),
                                AppointmentTypeConverter::class,
                                'convertFrom'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::create(StandardAppointmentType::class),
                                TypeDescriptor::createStringType(),
                                AppointmentTypeConverter::class,
                                'convertFrom'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::create(TrialAppointmentType::class),
                                TypeDescriptor::createStringType(),
                                AppointmentTypeConverter::class,
                                'convertFrom'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::createStringType(),
                                TypeDescriptor::create(AppointmentType::class),
                                AppointmentTypeConverter::class,
                                'convertTo'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::createStringType(),
                                TypeDescriptor::create(StandardAppointmentType::class),
                                AppointmentTypeConverter::class,
                                'convertTo'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::createStringType(),
                                TypeDescriptor::create(TrialAppointmentType::class),
                                AppointmentTypeConverter::class,
                                'convertTo'
                            ),
                        ],
                        JMSConverterConfiguration::createWithDefaults(),
                        null
                    )
                ),
            $configuration,
        );
    }

    public function test_not_registering_converter_from_simple_type_to_simple_type()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([SimpleTypeToSimpleType::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), null)),
            $configuration,
        );
    }

    public function test_always_registering_with_cache()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([SimpleTypeToSimpleType::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration            = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath('/tmp')
            ->withEnvironment('dev');
        $annotationConfiguration->prepare($configuration, [$applicationConfiguration], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), '/tmp')),
            $configuration,
        );
    }

    public function test_not_registering_converter_from_class_to_class()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([ClassToClassConverter::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), null)),
            $configuration,
        );
    }

    public function test_not_registering_converter_from_iterable_to_iterable()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([ArrayToArrayConverter::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), null)),
            $configuration,
        );
    }

    public function test_registering_converter_from_array_to_class()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(
            InMemoryAnnotationFinder::createFrom([ClassToArrayConverter::class]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(
                    new JMSConverterBuilder(
                        [
                            JMSHandlerAdapter::create(
                                TypeDescriptor::createArrayType(),
                                TypeDescriptor::create(stdClass::class),
                                ClassToArrayConverter::class,
                                'convertFrom'
                            ),
                            JMSHandlerAdapter::create(
                                TypeDescriptor::create(stdClass::class),
                                TypeDescriptor::createArrayType(),
                                ClassToArrayConverter::class,
                                'convertTo'
                            ),
                        ],
                        JMSConverterConfiguration::createWithDefaults(),
                        null
                    )
                ),
            $configuration,
        );
    }

    public function test_configuring_with_different_options()
    {
        $annotationConfiguration = JMSConverterConfigurationModule::create(InMemoryAnnotationFinder::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $configuration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerConverter(
                    new JMSConverterBuilder([], JMSConverterConfiguration::createWithDefaults(), null)
                ),
            $configuration,
        );
    }
}
