<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class ChainMessageHandlerBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Chain
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChainMessageHandlerBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var MessageHandlerBuilderWithOutputChannel[]
     */
    private $messageHandlerBuilders;

    /**
     * ChainMessageHandlerBuilder constructor.
     * @param string $inputChannelName
     */
    private function __construct(string $inputChannelName)
    {
        $this->withInputChannelName($inputChannelName);
    }

    /**
     * @param string $inputChannelName
     * @return ChainMessageHandlerBuilder
     */
    public static function createWith(string $inputChannelName) : self
    {
        return new self($inputChannelName);
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function chain(MessageHandlerBuilderWithOutputChannel $messageHandler) : self
    {
        $this->messageHandlerBuilders[] = $messageHandler;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var DirectChannel[] $bridgeChannels */
        $bridgeChannels = [];
        for ($key = 1; $key < count($this->messageHandlerBuilders); $key++) {
            $bridgeChannels[$key] = DirectChannel::create();
        }

        $customChannelResolver = InMemoryChannelResolver::createWithChanneResolver($channelResolver, $bridgeChannels);
        $firstMessageHandler = null;
        for ($key = 0; $key < count($this->messageHandlerBuilders); $key++) {
            $messageHandlerBuilder = $this->messageHandlerBuilders[$key];
            $nextHandlerKey = $key + 1;
            $previousHandlerKey = $key - 1;

            if ($this->hasNextHandler($nextHandlerKey)) {
                $messageHandlerBuilder->withOutputMessageChannel((string)($nextHandlerKey));
            }
            if (!$this->hasNextHandler($nextHandlerKey) && $this->outputMessageChannelName) {
                $messageHandlerBuilder = $messageHandlerBuilder
                                            ->withOutputMessageChannel($this->outputMessageChannelName);
            }

            $messageHandler = $messageHandlerBuilder->build($customChannelResolver, $referenceSearchService);

            if ($this->hasPreviousHandler($previousHandlerKey)) {
                $customChannelResolver->resolve($key)->subscribe($messageHandler);
            }

            if (!$firstMessageHandler) {
                $firstMessageHandler = $messageHandler;
            }
        }

        return $firstMessageHandler;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    /**
     * @param $nextHandlerKey
     * @return bool
     */
    private function hasNextHandler($nextHandlerKey): bool
    {
        return isset($this->messageHandlerBuilders[$nextHandlerKey]);
    }

    /**
     * @param $previousHandlerKey
     * @return bool
     */
    private function hasPreviousHandler($previousHandlerKey): bool
    {
        return isset($this->messageHandlerBuilders[$previousHandlerKey]);
    }
}