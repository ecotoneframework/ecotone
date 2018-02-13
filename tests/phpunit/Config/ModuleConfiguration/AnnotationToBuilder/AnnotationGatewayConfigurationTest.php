<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use Fixture\Annotation\MessageEndpoint\Gateway\GatewayWithReplyChannelExample;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder\AnnotationGatewayConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AnnotationGatewayConfigurationTest extends AnnotationConfigurationTest
{
    public function test_creating_transformer_builder()
    {
        $configuration = $this->createMessagingSystemConfiguration();

        $this->annotationConfiguration->registerWithin($configuration);

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
    protected function createAnnotationConfiguration(): AnnotationConfiguration
    {
        return new AnnotationGatewayConfiguration();
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Gateway";
    }
}