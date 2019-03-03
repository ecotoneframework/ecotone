<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\EndpointIdAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\HeaderAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\Interceptor\ServiceActivatorInterceptorTestBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\MessageParameterAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\PayloadAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\ReferenceAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Builder\Annotation\ServiceActivatorAnnotationTestCaseBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\Messaging\Annotation\EndpointId;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
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
        $serviceActivatorAnnotation = ServiceActivatorAnnotationTestCaseBuilder::create()
            ->withEndpointId("test-name")
            ->withInputChannelName("inputChannel")
            ->withOutputChannelName("outputChannel")
            ->withRequiresReply(true)
            ->withParameterConverters([
                HeaderAnnotationTestCaseBuilder::create("to", "sendTo"),
                PayloadAnnotationTestCaseBuilder::create("content"),
                MessageParameterAnnotationTestCaseBuilder::create("message"),
                ReferenceAnnotationTestCaseBuilder::create("object", "reference")
            ])
            ->build();

        $annotationConfiguration = ServiceActivatorModule::create(
            $this->createAnnotationRegistrationService(
                ServiceActivatorWithAllConfigurationDefined::class,
                "sendMessage",
                new MessageEndpoint(),
                $serviceActivatorAnnotation
            )
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
                            ReferenceBuilder::create("object", "reference")
                        ])
                        ->withRequiredReply(true)
                )
        );
    }
}