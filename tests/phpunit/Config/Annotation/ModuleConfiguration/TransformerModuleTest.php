<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\TransformerAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\RouterModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $transformerAnnotation = new TransformerAnnotation();
        $transformerAnnotation->inputChannelName = "inputChannel";
        $transformerAnnotation->outputChannelName = "outputChannel";
        $messageToPayload = new MessageToPayloadParameterAnnotation();
        $messageToPayload->parameterName = "message";
        $transformerAnnotation->parameterConverters = [$messageToPayload];

        $annotationConfiguration = TransformerModule::create(
            $this->createAnnotationRegistrationService(
                TransformerWithMethodParameterExample::class,
                "send",
                new MessageEndpointAnnotation(),
                $transformerAnnotation
            )
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->registerWithin($configuration, [], InMemoryConfigurationVariableRetrievingService::createEmpty(), InMemoryReferenceSearchService::createEmpty());

        $messageHandlerBuilder = TransformerBuilder::create(
            "inputChannel", TransformerWithMethodParameterExample::class, "send"
        )->withOutputMessageChannel("outputChannel");
        $messageHandlerBuilder->withMethodParameterConverters([
            MessageToPayloadParameterConverterBuilder::create("message")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}