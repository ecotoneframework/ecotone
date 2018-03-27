<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\AllConfigurationDefined\ServiceActivatorWithAllConfigurationDefined;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToHeaderParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToReferenceServiceAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ServiceActivatorAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToHeaderParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToReferenceServiceParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class AnnotationServiceActivatorConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceActivatorModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_service_activator_builder_from_annotation()
    {
        $serviceActivatorAnnotation = new ServiceActivatorAnnotation();
        $serviceActivatorAnnotation->inputChannel = "inputChannel";
        $serviceActivatorAnnotation->outputChannel = "outputChannel";
        $serviceActivatorAnnotation->requiresReply = true;
        $messageToHeaderConverter = new MessageToHeaderParameterAnnotation();
        $messageToHeaderConverter->parameterName = "to";
        $messageToHeaderConverter->headerName = "sendTo";
        $payloadParameterConverter = new MessageToPayloadParameterAnnotation();
        $payloadParameterConverter->parameterName = "content";
        $messageParameterConverter = new MessageParameterAnnotation();
        $messageParameterConverter->parameterName = "message";
        $referenceServiceConverter = new MessageToReferenceServiceAnnotation();
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
                new MessageEndpointAnnotation(),
                $serviceActivatorAnnotation

            )
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->registerWithin($configuration, [], InMemoryConfigurationVariableRetrievingService::createEmpty(), InMemoryReferenceSearchService::createEmpty());

        $serviceActivatorBuilder = ServiceActivatorBuilder::create(ServiceActivatorWithAllConfigurationDefined::class, "sendMessage");
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
                        ->withInputMessageChannel("inputChannel")
                        ->withOutputChannel('outputChannel')
                        ->withRequiredReply(true)
                )
        );
    }
}