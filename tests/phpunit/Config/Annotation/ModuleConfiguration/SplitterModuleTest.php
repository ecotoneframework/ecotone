<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Splitter\SplitterExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Splitter;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\SplitterModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $splitterAnnotation = new Splitter();
        $splitterAnnotation->inputChannelName = "inputChannel";
        $splitterAnnotation->outputChannelName = "outputChannel";
        $messageToPayloadParameterAnnotation = new Payload();
        $messageToPayloadParameterAnnotation->parameterName = "payload";
        $splitterAnnotation->parameterConverters = [$messageToPayloadParameterAnnotation];

        $annotationConfiguration = SplitterModule::create(
            $this->createAnnotationRegistrationService(
                SplitterExample::class,
                "split",
                new MessageEndpoint(),
                $splitterAnnotation

            )
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $messageHandlerBuilder = SplitterBuilder::create(
            SplitterExample::class,  "split"
        )
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel("outputChannel");
        $messageHandlerBuilder->withMethodParameterConverters([
            PayloadBuilder::create("payload")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}