<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Precedence;
use ReflectionException;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor\MethodInterceptorModule;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\MessagingException;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\AroundInterceptorWithCustomParameterConverters;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ServiceActivatorInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\ServiceActivatorInterceptorWithServicesExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\TransformerInterceptorExample;

/**
 * Class MethodInterceptorModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws ConfigurationException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     */
    public function test_registering_around_method_level_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create(CalculatingServiceInterceptorExample::class, "calculatingService", "sum", 2, CalculatingServiceInterceptorExample::class, []))
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create(CalculatingServiceInterceptorExample::class, "calculatingService", "subtract", Precedence::DEFAULT_PRECEDENCE, "", []))
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create(CalculatingServiceInterceptorExample::class, "calculatingService", "multiply", 2, CalculatingServiceInterceptorExample::class, []));

        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom([
            CalculatingServiceInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
        $this->assertEquals(
            ["calculatingService"],
            $configuration->getRequiredReferences()
        );
    }

    public function test_registering_around_method_level_interceptor_with_parameter_converters()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::create(AroundInterceptorWithCustomParameterConverters::class, AroundInterceptorWithCustomParameterConverters::class, "handle", 1, AroundInterceptorWithCustomParameterConverters::class, [
                    HeaderBuilder::create("token", "token"),
                    PayloadBuilder::create("payload"),
                    AllHeadersBuilder::createWith("headers")
                ])
            );

        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom([
            AroundInterceptorWithCustomParameterConverters::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
    }

    public function test_registering_before_and_after_with_payload_modification()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
                    InterfaceToCall::create(ServiceActivatorInterceptorExample::class, "doSomethingBefore"),
                    ServiceActivatorBuilder::create("someMethodInterceptor", "doSomethingBefore")
                        ->withPassThroughMessageOnVoidInterface(true)
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    2,
                    ServiceActivatorInterceptorExample::class

                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
                    InterfaceToCall::create(ServiceActivatorInterceptorExample::class, "doSomethingAfter"),
                    ServiceActivatorBuilder::create("someMethodInterceptor", "doSomethingAfter")
                        ->withPassThroughMessageOnVoidInterface(true)
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    1,
                    ""

                )
            );

        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom([
            ServiceActivatorInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
        $this->assertEquals(
            ["someMethodInterceptor"],
            $configuration->getRequiredReferences()
        );
    }

    public function test_registering_transformer_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerBeforeSendInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
                    InterfaceToCall::create(TransformerInterceptorExample::class, "beforeSend"),
                    TransformerBuilder::create("someMethodInterceptor", "beforeSend")
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    2,
                    ServiceActivatorInterceptorExample::class

                )
            )
            ->registerRelatedInterfaces([InterfaceToCall::create(TransformerInterceptorExample::class, "beforeSend")])
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
                    InterfaceToCall::create(TransformerInterceptorExample::class, "doSomethingBefore"),
                    TransformerBuilder::create("someMethodInterceptor", "doSomethingBefore")
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    2,
                    ServiceActivatorInterceptorExample::class

                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
                    InterfaceToCall::create(TransformerInterceptorExample::class, "doSomethingAfter"),
                    TransformerBuilder::create("someMethodInterceptor", "doSomethingAfter")
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    1,
                    ""

                )
            );

        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom([
            TransformerInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
        $this->assertEquals(
            ["someMethodInterceptor"],
            $configuration->getRequiredReferences()
        );
    }
}