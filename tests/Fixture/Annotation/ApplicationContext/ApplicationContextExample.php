<?php

namespace Fixture\Annotation\ApplicationContext;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class ApplicationContext
 * @package Fixture\Annotation\ApplicationContext
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContextAnnotation()
 */
class ApplicationContextExample
{
    const HTTP_INPUT_CHANNEL = "httpEntry";
    const HTTP_OUTPUT_CHANNEL = "httpOutput";

    /**
     * @return MessageChannelBuilder
     * @MessagingComponentAnnotation()
     */
    public function httpEntryChannel(): MessageChannelBuilder
    {
        return SimpleMessageChannelBuilder::createDirectMessageChannel(self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageChannelBuilder
     * @MessagingComponentAnnotation()
     */
    public function httpOutputChannel() : MessageChannelBuilder
    {
        return SimpleMessageChannelBuilder::createQueueChannel(self::HTTP_OUTPUT_CHANNEL);
    }

    /**
     * @return MessageHandlerBuilder
     * @MessagingComponentAnnotation()
     */
    public function enricherHttpEntry() : MessageHandlerBuilder
    {
        return TransformerBuilder::createHeaderEnricher("http-entry-enricher", self::HTTP_INPUT_CHANNEL, self::HTTP_OUTPUT_CHANNEL, [
            "token" => "abcedfg"
        ]);
    }

    /**
     * @return GatewayBuilder
     * @MessagingComponentAnnotation()
     */
    public function gateway() : GatewayBuilder
    {
        return GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", self::HTTP_INPUT_CHANNEL);
    }
}