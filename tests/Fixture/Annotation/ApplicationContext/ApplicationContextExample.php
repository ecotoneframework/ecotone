<?php
declare(strict_types=1);

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
     * @return MessageHandlerBuilder
     * @MessagingComponentAnnotation()
     */
    public function enricherHttpEntry() : MessageHandlerBuilder
    {
        return TransformerBuilder::createHeaderEnricher(self::HTTP_INPUT_CHANNEL, [
            "token" => "abcedfg"
        ])->withOutputMessageChannel(self::HTTP_OUTPUT_CHANNEL);
    }

    /**
     * @return GatewayBuilder
     * @MessagingComponentAnnotation()
     */
    public function gateway() : GatewayBuilder
    {
        return GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return array
     * @MessagingComponentAnnotation()
     */
    public function withMultipleMessageComponents() : array
    {
        return [
            $this->httpEntryChannel(),
            $this->enricherHttpEntry()
        ];
    }

    /**
     * @return \stdClass
     */
    public function wrongMessagingComponent() : \stdClass
    {
        return new \stdClass();
    }
}