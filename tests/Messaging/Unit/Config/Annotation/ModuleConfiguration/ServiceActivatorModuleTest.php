<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ValueBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\HeaderAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\MessageParameterAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\PayloadAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\ReferenceAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\ServiceActivatorAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorModuleTest extends AnnotationConfigurationTest
{
    /**
     */
    public function test_creating_service_activator_builder_from_annotation()
    {
        $annotationConfiguration = ServiceActivatorModule::create(
            InMemoryAnnotationRegistrationService::createFrom([
                ServiceActivatorWithAllConfigurationDefined::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage")
                        ->withEndpointId("test-name")
                        ->withInputChannelName("inputChannel")
                        ->withOutputMessageChannel('outputChannel')
                        ->withMethodParameterConverters([
                            HeaderBuilder::create("to", "sendTo"),
                            PayloadBuilder::create("content"),
                            MessageConverterBuilder::create("message"),
                            ReferenceBuilder::create("object", "reference"),
                            ValueBuilder::create("name", "some")
                        ])
                        ->withRequiredReply(true)
                        ->withRequiredInterceptorNames(["someReference"])
                )
        );
    }
}