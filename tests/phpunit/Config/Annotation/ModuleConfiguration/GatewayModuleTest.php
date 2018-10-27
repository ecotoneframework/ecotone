<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use Fixture\Annotation\MessageEndpoint\Gateway\BookStoreGatewayExample;
use Fixture\Annotation\MessageEndpoint\Gateway\GatewayWithReplyChannelExample;
use Fixture\Handler\Gateway\MultipleMethodsGatewayExample;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\NullObserver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\CombinedGatewayDefinition;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_gateway()
    {
        $annotationGatewayConfiguration = GatewayModule::create(
            InMemoryAnnotationRegistrationService::createFrom([BookStoreGatewayExample::class])
        );

        $messagingSystemConfiguration = $this->createMessagingSystemConfiguration();
        $annotationGatewayConfiguration->prepare($messagingSystemConfiguration, []);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        BookStoreGatewayExample::class, BookStoreGatewayExample::class,
                        "rent", "requestChannel"
                    )
                        ->withErrorChannel("errorChannel")
                        ->withTransactionFactories(['dbalTransaction'])
                        ->withParameterToMessageConverters([
                            GatewayPayloadExpressionBuilder::create("bookNumber", "upper(value)"),
                            GatewayHeaderBuilder::create("rentTill", "rentDate"),
                            GatewayHeaderExpressionBuilder::create("cost", "cost", "value * 5"),
                            GatewayHeaderValueBuilder::create("owner", "Johny")
                        ])
                ),
            $messagingSystemConfiguration
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function test_registering_gateway_with_multiple_methods()
    {
        $annotationGatewayConfiguration = GatewayModule::create(InMemoryAnnotationRegistrationService::createFrom([MultipleMethodsGatewayExample::class]));

        $messagingSystemConfiguration = $this->createMessagingSystemConfiguration();
        $annotationGatewayConfiguration->prepare($messagingSystemConfiguration, []);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    CombinedGatewayBuilder::create(
                        MultipleMethodsGatewayExample::class,
                        MultipleMethodsGatewayExample::class,
                        [
                            CombinedGatewayDefinition::create(
                                GatewayProxyBuilder::create(
                                    MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                                    "execute1", "channel1"
                                ),
                                "execute1"
                            ),
                            CombinedGatewayDefinition::create(
                                GatewayProxyBuilder::create(
                                    MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                                    "execute2", "channel2"
                                ),
                                "execute2"
                            )
                        ]
                    )
                ),
            $messagingSystemConfiguration
        );
    }

    /**
     * @inheritDoc
     */
    protected function createAnnotationConfiguration(): string
    {
        return GatewayModule::class;
    }

    /**
     * @inheritDoc
     */
    protected function getPartOfTheNamespaceForTests(): string
    {
        return "MessageEndpoint\Gateway";
    }
}