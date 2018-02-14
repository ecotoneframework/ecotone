<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation;

use Fixture\Annotation\MessageEndpoint\Gateway\GatewayWithReplyChannelExample;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation\AnnotationGatewayConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationGatewayConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_transformer_builder()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration, InMemoryConfigurationVariableRetrievingService::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(GatewayProxyBuilder::create(
                    GatewayWithReplyChannelExample::class, GatewayWithReplyChannelExample::class,
                    "buy", "requestChannel"
                )->withMillisecondTimeout(1)),
            $configuration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): string
    {
        return AnnotationGatewayConfiguration::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Gateway";
    }
}