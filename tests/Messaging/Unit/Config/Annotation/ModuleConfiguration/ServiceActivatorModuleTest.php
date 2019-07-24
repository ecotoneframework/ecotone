<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;

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
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

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
                            PayloadExpressionBuilder::create("content2", "value"),
                            MessageConverterBuilder::create("message"),
                            ReferenceBuilder::create("object", "reference"),
                            HeaderExpressionBuilder::create("name", "token", "value", false)
                        ])
                        ->withRequiredReply(true)
                        ->withRequiredInterceptorNames(["someReference"])
                )
        );
    }
}