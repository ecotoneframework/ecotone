<?php
declare(strict_types=1);

namespace Fixture\Annotation\ApplicationContext;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponent;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Transformer\TransformerBuilder;

/**
 * Class ApplicationContext
 * @package Fixture\Annotation\ApplicationContext
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ApplicationContextExample
{
    const HTTP_INPUT_CHANNEL = "httpEntry";
    const HTTP_OUTPUT_CHANNEL = "httpOutput";

    /**
     * @return GatewayBuilder
     * @MessagingComponent()
     */
    public function gateway(): GatewayBuilder
    {
        return GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return array
     * @MessagingComponent()
     */
    public function withMultipleMessageComponents(): array
    {
        return [
            $this->httpEntryChannel(),
            $this->enricherHttpEntry()
        ];
    }

    /**
     * @return MessageChannelBuilder
     * @MessagingComponent()
     */
    public function httpEntryChannel(): MessageChannelBuilder
    {
        return SimpleMessageChannelBuilder::createDirectMessageChannel(self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageHandlerBuilder
     * @MessagingComponent()
     */
    public function enricherHttpEntry(): MessageHandlerBuilder
    {
        return TransformerBuilder::createHeaderEnricher([
            "token" => "abcedfg"
        ])
            ->withInputChannelName(self::HTTP_INPUT_CHANNEL)
            ->withOutputMessageChannel(self::HTTP_OUTPUT_CHANNEL)
            ->withEndpointId("some-id");
    }

    /**
     * @return array
     * @MessagingComponent()
     */
    public function withChannelInterceptors(): array
    {
        return [
            $this->httpEntryChannel(),
            SimpleChannelInterceptorBuilder::create(self::HTTP_INPUT_CHANNEL, "ref")
        ];
    }

    /**
     * @return \stdClass
     * @MessagingComponent()
     */
    public function withStdClassConverterByExtension(): \stdClass
    {
        return new \stdClass();
    }

    /**
     * @return \stdClass
     */
    public function wrongMessagingComponent(): \stdClass
    {
        return new \stdClass();
    }
}