<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpointAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter\MessageToPayloadParameterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\SplitterAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     */
    public function test_creating_transformer_builder()
    {
        $splitterAnnotation = new SplitterAnnotation();
        $splitterAnnotation->inputChannelName = "inputChannel";
        $splitterAnnotation->outputChannelName = "outputChannel";
        $messageToPayloadParameterAnnotation = new MessageToPayloadParameterAnnotation();
        $messageToPayloadParameterAnnotation->parameterName = "payload";
        $splitterAnnotation->parameterConverters = [$messageToPayloadParameterAnnotation];

        $annotationConfiguration = SplitterModule::create(
            $this->createAnnotationRegistrationService(
                SplitterExample::class,
                "split",
                new MessageEndpointAnnotation(),
                $splitterAnnotation

            )
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->registerWithin($configuration, [], InMemoryConfigurationVariableRetrievingService::createEmpty());

        $messageHandlerBuilder = SplitterBuilder::create(
            "inputChannel", SplitterExample::class,  "split"
        )
            ->withOutputChannel("outputChannel");
        $messageHandlerBuilder->withMethodParameterConverters([
            MessageToPayloadParameterConverterBuilder::create("payload")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}