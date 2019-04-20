<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\GatewayModule;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\CombinedGatewayDefinition;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderArrayBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderExpressionBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadExpressionBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\BookStoreGatewayExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Gateway\MultipleMethodsGatewayExample;

/**
 * Class AnnotationTransformerConfigurationTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Config\Annotation\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayModuleTest extends AnnotationConfigurationTest
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
                        ->withReplyMillisecondTimeout(100)
                        ->withParameterConverters([
                            GatewayPayloadExpressionBuilder::create("bookNumber", "upper(value)"),
                            GatewayHeaderBuilder::create("rentTill", "rentDate"),
                            GatewayHeaderExpressionBuilder::create("cost", "cost", "value * 5"),
                            GatewayHeaderValueBuilder::create("owner", "Johny"),
                            GatewayHeaderArrayBuilder::create("data")
                        ])
                ),
            $messagingSystemConfiguration
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_gateway_with_multiple_methods()
    {
        $annotationGatewayConfiguration = GatewayModule::create(InMemoryAnnotationRegistrationService::createFrom([MultipleMethodsGatewayExample::class]));

        $messagingSystemConfiguration = $this->createMessagingSystemConfiguration();
        $annotationGatewayConfiguration->prepare($messagingSystemConfiguration, []);

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                        "execute1", "channel1"
                    )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(
                        MultipleMethodsGatewayExample::class, MultipleMethodsGatewayExample::class,
                        "execute2", "channel2"
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