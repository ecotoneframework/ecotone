<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptorModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\ServiceActivatorInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\TransformerInterceptorExample;

/**
 * Class MethodInterceptorModuleTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodInterceptorModuleTest extends AnnotationConfigurationTest
{
    /**
     * @return mixed
     * @throws ConfigurationException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_around_method_level_interceptor()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create("calculatingService","calculatingService", "sum", 2, CalculatingServiceInterceptorExample::class))
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create("calculatingService","calculatingService", "subtract", MethodInterceptor::DEFAULT_PRECEDENCE, ""))
            ->registerAroundMethodInterceptor(AroundInterceptorReference::create("calculatingService","calculatingService", "multiply", 2, CalculatingServiceInterceptorExample::class));

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            CalculatingServiceInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $expectedConfiguration,
            $configuration
        );
        $this->assertEquals(
            ["calculatingService"],
            $configuration->getRequiredReferences()
        );
    }


    public function test_registering_before_and_after_with_payload_modification()
    {
        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
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

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            ServiceActivatorInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

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
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "someMethodInterceptor",
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
                    TransformerBuilder::create("someMethodInterceptor", "doSomethingAfter")
                        ->withMethodParameterConverters([
                            PayloadBuilder::create("name"),
                            HeaderBuilder::create("surname", "surname")
                        ]),
                    1,
                    ""

                )
            );

        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom([
            TransformerInterceptorExample::class
        ]);
        $annotationConfiguration = MethodInterceptorModule::create($annotationRegistrationService);
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

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