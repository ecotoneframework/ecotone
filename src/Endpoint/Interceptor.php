<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\Assert;

/**
 * Class Interceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Interceptor
{
    /**
     * @var string
     */
    private $interceptedMessageHandlerName;
    /**
     * @var MessageHandlerBuilderWithOutputChannel
     */
    private $interceptorMessageHandler;

    /**
     * Interceptor constructor.
     * @param string $interceptedMessageHandlerName
     * @param MessageHandlerBuilderWithOutputChannel $interceptorMessageHandlerBuilder
     */
    private function __construct(string $interceptedMessageHandlerName, MessageHandlerBuilderWithOutputChannel $interceptorMessageHandlerBuilder)
    {
        $this->interceptedMessageHandlerName = $interceptedMessageHandlerName;
        $this->interceptorMessageHandler = $interceptorMessageHandlerBuilder;
    }

    /**
     * @param string $interceptedMessageHandlerName
     * @param MessageHandlerBuilder $interceptorMessageHandler
     * @return Interceptor
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public static function create(string $interceptedMessageHandlerName, MessageHandlerBuilder $interceptorMessageHandler) : self
    {
        Assert::isTrue(\assert($interceptorMessageHandler instanceof MessageHandlerBuilderWithOutputChannel), "Wrong type of interceptor for {$interceptedMessageHandlerName}. Message Handler interceptor should allow output channels");

        return new self($interceptedMessageHandlerName, $interceptorMessageHandler);
    }

    /**
     * @return string
     */
    public function getInterceptedMessageHandlerName(): string
    {
        return $this->interceptedMessageHandlerName;
    }

    /**
     * @return MessageHandlerBuilderWithOutputChannel
     */
    public function getInterceptorMessageHandler(): MessageHandlerBuilderWithOutputChannel
    {
        return $this->interceptorMessageHandler;
    }
}