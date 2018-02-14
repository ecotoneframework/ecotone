<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation\AnnotationTransformerConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationTransformerConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_transformer_builder()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());

        $messageHandlerBuilder = TransformerBuilder::create(
            "inputChannel", "outputChannel", TransformerWithMethodParameterExample::class, "send", TransformerWithMethodParameterExample::class
        );
        $messageHandlerBuilder->withMethodParameterConverters([
            MessageToPayloadParameterConverterBuilder::create("message")
        ]);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($messageHandlerBuilder),
            $configuration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): string
    {
        return AnnotationTransformerConfiguration::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Transformer";
    }
}