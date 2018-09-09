<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Builder\Annotation\EndpointIdTestBuilder;
use Builder\Annotation\HeaderTestBuilder;
use Builder\Annotation\Interceptor\ServiceActivatorInterceptorTestBuilder;
use Builder\Annotation\MessageParameterTestBuilder;
use Builder\Annotation\PayloadTestBuilder;
use Builder\Annotation\ReferenceTestBuilder;
use Builder\Annotation\ServiceActivatorTestBuilder;
use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\EndpointId;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Header;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\MessageParameter;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\HeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorModuleTest extends AnnotationConfigurationTest
{
    /**
     */
    public function test_creating_service_activator_builder_from_annotation()
    {
        $serviceActivatorAnnotation = ServiceActivatorTestBuilder::create()
            ->withEndpointId("test-name")
            ->withInputChannelName("inputChannel")
            ->withOutputChannelName("outputChannel")
            ->withRequiresReply(true)
            ->withParameterConverters([
                HeaderTestBuilder::create("to", "sendTo"),
                PayloadTestBuilder::create("content"),
                MessageParameterTestBuilder::create("message"),
                ReferenceTestBuilder::create("object", "reference")
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
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

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
                        ->registerRequiredReference("reference")
                        ->withRequiredReply(true)
                )
        );
    }
}