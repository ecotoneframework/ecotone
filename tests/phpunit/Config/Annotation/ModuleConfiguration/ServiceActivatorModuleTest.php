<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
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
        $serviceActivatorAnnotation = new ServiceActivator();
        $serviceActivatorAnnotation->inputChannelName = "inputChannel";
        $serviceActivatorAnnotation->outputChannelName = "outputChannel";
        $serviceActivatorAnnotation->requiresReply = true;
        $messageToHeaderConverter = new Header();
        $messageToHeaderConverter->parameterName = "to";
        $messageToHeaderConverter->headerName = "sendTo";
        $payloadParameterConverter = new Payload();
        $payloadParameterConverter->parameterName = "content";
        $messageParameterConverter = new MessageParameter();
        $messageParameterConverter->parameterName = "message";
        $referenceServiceConverter = new Reference();
        $referenceServiceConverter->parameterName = "object";
        $referenceServiceConverter->referenceName = "reference";
        $parameterConverters = [
            $messageToHeaderConverter, $payloadParameterConverter, $messageParameterConverter, $referenceServiceConverter
        ];
        $serviceActivatorAnnotation->parameterConverters = $parameterConverters;

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

        $serviceActivatorBuilder = ServiceActivatorBuilder::create(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage")
                                    ->withInputChannelName("inputChannel");
        $serviceActivatorBuilder->withMethodParameterConverters([
            HeaderBuilder::create("to", "sendTo"),
            PayloadBuilder::create("content"),
            MessageConverterBuilder::create("message"),
            ReferenceBuilder::create("object", "reference")
        ]);
        $serviceActivatorBuilder->registerRequiredReference("reference");

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler(
                    $serviceActivatorBuilder
                        ->withOutputMessageChannel('outputChannel')
                        ->withRequiredReply(true)
                )
        );
    }
}