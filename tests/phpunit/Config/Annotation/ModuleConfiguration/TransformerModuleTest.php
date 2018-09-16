<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Transformer;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $transformerAnnotation = new Transformer();
        $transformerAnnotation->inputChannelName = "inputChannel";
        $transformerAnnotation->outputChannelName = "outputChannel";
        $messageToPayload = new Payload();
        $messageToPayload->parameterName = "message";
        $transformerAnnotation->parameterConverters = [$messageToPayload];

        $annotationConfiguration = TransformerModule::create(
            $this->createAnnotationRegistrationService(
                TransformerWithMethodParameterExample::class,
                "send",
                new MessageEndpoint(),
                $transformerAnnotation
            )
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], NullObserver::create());

        $messageHandlerBuilder = TransformerBuilder::create(
            TransformerWithMethodParameterExample::class, "send"
        )
            ->withInputChannelName("inputChannel")
            ->withOutputMessageChannel("outputChannel");
        $messageHandlerBuilder->withMethodParameterConverters([
            PayloadBuilder::create("message")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }
}