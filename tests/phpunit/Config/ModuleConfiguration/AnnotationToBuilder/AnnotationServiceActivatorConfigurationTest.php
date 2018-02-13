<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationServiceActivatorConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationServiceActivatorConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_service_activator_builder_from_annotation()
    {
        $configuration = $this->createMessagingSystemConfiguration();
        $this->annotationConfiguration->registerWithin($configuration);

        $serviceActivatorBuilder = ServiceActivatorBuilder::create("message_sender", "sendMessage");
        $serviceActivatorBuilder->withMethodParameterConverters([
            MessageToHeaderParameterConverterBuilder::create("to", "sendTo"),
            MessageToPayloadParameterConverterBuilder::create("content"),
            MessageParameterConverterBuilder::create("message"),
            MessageToReferenceServiceParameterConverterBuilder::create("object", "reference", $serviceActivatorBuilder)
        ]);
        $serviceActivatorBuilder->registerRequiredReference("reference");

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler(
                    $serviceActivatorBuilder
                        ->withConsumerName("message_sender")
                        ->withInputMessageChannel("inputChannel")
                        ->withOutputChannel('outputChannel')
                        ->withRequiredReply(true)
                )
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationServiceActivatorConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\ServiceActivator\AllConfigurationDefined";
    }
}