<?php

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadExpressionBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ValueBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\Annotation
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