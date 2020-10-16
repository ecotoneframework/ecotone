<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ApplicationContext;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use stdClass;

class ApplicationContextExample
{
    const HTTP_INPUT_CHANNEL  = "httpEntry";
    const HTTP_OUTPUT_CHANNEL = "httpOutput";

    /**
     * @return GatewayBuilder
     * @ApplicationContext()
     */
    public function gateway(): GatewayBuilder
    {
        return GatewayProxyBuilder::create("some-ref", GatewayExample::class, "doSomething", self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageChannelBuilder
     * @ApplicationContext()
     */
    public function httpEntryChannel(): MessageChannelBuilder
    {
        return SimpleMessageChannelBuilder::createDirectMessageChannel(self::HTTP_INPUT_CHANNEL);
    }

    /**
     * @return MessageHandlerBuilder
     * @ApplicationContext()
     */
    public function enricherHttpEntry(): MessageHandlerBuilder
    {
        return TransformerBuilder::createHeaderEnricher(
            [
                "token" => "abcedfg"
            ]
        )
            ->withInputChannelName(self::HTTP_INPUT_CHANNEL)
            ->withOutputMessageChannel(self::HTTP_OUTPUT_CHANNEL)
            ->withEndpointId("some-id");
    }

    /**
     * @ApplicationContext()
     */
    public function withChannelInterceptors()
    {
        return SimpleChannelInterceptorBuilder::create(self::HTTP_INPUT_CHANNEL, "ref");
    }

    /**
     * @return stdClass
     * @ApplicationContext()
     */
    public function withStdClassConverterByExtension(): stdClass
    {
        return new stdClass();
    }

    /**
     * @return stdClass
     */
    public function wrongExtensionObject(): stdClass
    {
        return new stdClass();
    }
}