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
     * @var string[]
     */
    private $requiredReferences = [];

    /**
     * ChainMessageHandlerBuilder constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return ChainMessageHandlerBuilder
     */
    public static function create() : self
    {
        return new self();
    }

    /**
     * @param MessageHandlerBuilderWithOutputChannel $messageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function chain(MessageHandlerBuilderWithOutputChannel $messageHandler) : self
    {
        $this->messageHandlerBuilders[] = $messageHandler;
        foreach ($messageHandler->getRequiredReferenceNames() as $referenceName) {
            $this->requiredReferences[] = $referenceName;
        }

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
        return $this->requiredReferences;
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