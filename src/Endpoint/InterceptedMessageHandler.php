<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class Interceptor
 * @package SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptedMessageHandler extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithOutputChannel
{
    /**
     * @var MessageHandlerBuilder
     */
    private $interceptedMessageHandlerBuilder;
    /**
     * @var array|MessageHandlerBuilderWithOutputChannel[]
     */
    private $preCallInterceptors;
    /**
     * @var array|MessageHandlerBuilderWithOutputChannel[]
     */
    private $postCallInterceptors;

    /**
     * Interceptor constructor.
     * @param MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder
     * @param MessageHandlerBuilderWithOutputChannel[] $preCallMessageHandlers
     * @param MessageHandlerBuilderWithOutputChannel[] $postCallMessageHandlers
     */
    private function __construct(MessageHandlerBuilderWithOutputChannel $interceptedMessageHandlerBuilder, array $preCallMessageHandlers, array $postCallMessageHandlers)
    {
        $this->interceptedMessageHandlerBuilder = $interceptedMessageHandlerBuilder;
        $this->preCallInterceptors = $preCallMessageHandlers;
        $this->postCallInterceptors = $postCallMessageHandlers;

        $this->initialize();
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $interceptorMessageHandlerBuilder
     * @param MessageHandlerBuilderWithOutputChannel[] $preCallMessageHandlers
     * @param MessageHandlerBuilderWithOutputChannel[] $postCallMessageHandlers
     *
     * @return InterceptedMessageHandler
     */
    public static function create(MessageHandlerBuilderWithOutputChannel $interceptorMessageHandlerBuilder, array $preCallMessageHandlers, array $postCallMessageHandlers): self
    {
        return new self($interceptorMessageHandlerBuilder, $preCallMessageHandlers, $postCallMessageHandlers);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->interceptedMessageHandlerBuilder->getRequiredReferenceNames();
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $chainMessageHandler = ChainMessageHandlerBuilder::create()
                                ->withOutputMessageChannel($this->outputMessageChannelName);

        foreach ($this->preCallInterceptors as $preCallMessageHandler) {
            $chainMessageHandler = $chainMessageHandler
                ->chain($preCallMessageHandler);
        }

        $chainMessageHandler = $chainMessageHandler->chain($this->interceptedMessageHandlerBuilder);

        foreach ($this->postCallInterceptors as $postCallMessageHandler) {
            $chainMessageHandler = $chainMessageHandler
                ->chain($postCallMessageHandler);
        }

        return $chainMessageHandler->build($channelResolver, $referenceSearchService);
    }

    private function initialize(): void
    {
        $this->withInputChannelName($this->interceptedMessageHandlerBuilder->getInputMessageChannelName());
    }
}