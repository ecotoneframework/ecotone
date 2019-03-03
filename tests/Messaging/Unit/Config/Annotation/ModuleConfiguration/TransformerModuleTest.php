<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Transformer;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\TransformerModule;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\NullObserver;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\PayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TransformerModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_transformer_builder()
    {
        $annotationConfiguration = TransformerModule::create(
            InMemoryAnnotationRegistrationService::createFrom([TransformerWithMethodParameterExample::class])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, []);

        $messageHandlerBuilder = TransformerBuilder::create(
            TransformerWithMethodParameterExample::class, "send"
        )
            ->withEndpointId("some-id")
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