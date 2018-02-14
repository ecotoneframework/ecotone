<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\ApplicationContext\ApplicationContextExample;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\AnnotationApplicationContextConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class AnnotationApplicationContextConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationApplicationContextConfigurationTest extends AnnotationConfigurationTest
{
    public function test_configuring_from_application_context()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());

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
    protected function createAnnotationConfiguration(): string
    {
        return AnnotationApplicationContextConfiguration::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "ApplicationContext";
    }
}