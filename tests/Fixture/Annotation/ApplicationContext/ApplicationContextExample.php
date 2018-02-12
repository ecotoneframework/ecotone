<?php

namespace Fixture\Annotation\ApplicationContext;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Transformer\TransformerBuilder;

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
        return TransformerBuilder::createHeaderEnricher(Uuid::uuid4(), self::HTTP_INPUT_CHANNEL, self::HTTP_OUTPUT_CHANNEL, [
            "token" => "abcedfg"
        ]);
    }
}