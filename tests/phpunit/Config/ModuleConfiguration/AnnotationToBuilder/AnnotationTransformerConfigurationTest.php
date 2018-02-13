<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Fixture\Annotation\MessageEndpoint\Transformer\TransformerWithMethodParameterExample;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationTransformerConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageToPayloadParameterConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationTransformerConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_transformer_builder()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration);

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
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationTransformerConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Transformer";
    }
}