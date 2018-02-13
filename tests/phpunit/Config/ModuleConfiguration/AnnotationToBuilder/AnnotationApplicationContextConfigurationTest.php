<?php

namespace Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationApplicationContextConfiguration;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationApplicationContextConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationApplicationContextConfigurationTest extends AnnotationConfigurationTest
{
    public function test_configuring_from_application_context()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(ApplicationContextExample::HTTP_INPUT_CHANNEL))
                ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel(ApplicationContextExample::HTTP_OUTPUT_CHANNEL))
                ->registerMessageHandler(TransformerBuilder::createHeaderEnricher("http-entry-enricher", ApplicationContextExample::HTTP_INPUT_CHANNEL, ApplicationContextExample::HTTP_OUTPUT_CHANNEL, [
                    "token" => "abcedfg"
                ])),
            $configuration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationApplicationContextConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "ApplicationContext";
    }
}