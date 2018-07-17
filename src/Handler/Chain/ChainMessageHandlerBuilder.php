<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Chain;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Config\NamedMessageChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilderWithOutputChannel;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

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
     * @var MessageHandlerBuilder
     */
    private $outputMessageHandler;

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
     * Do not combine with outputMessageChannel. Output message handler can be router and should contain output channel by his own
     *
     * @param MessageHandlerBuilder $outputMessageHandler
     * @return ChainMessageHandlerBuilder
     */
    public function withOutputMessageHandler(MessageHandlerBuilder $outputMessageHandler) : self
    {
        $this->outputMessageHandler = $outputMessageHandler;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        if ($this->outputMessageHandler && $this->outputMessageChannelName) {
            throw InvalidArgumentException::create("Can't configure output message handler and output message channel for chain handler");
        }

        /** @var DirectChannel[] $bridgeChannels */
        $bridgeChannels = [];
        $messageHandlersToChain = $this->messageHandlerBuilders;

        if ($this->outputMessageHandler) {
            $messageHandlersToChain[] = $this->outputMessageHandler;
        }

        for ($key = 1; $key < count($messageHandlersToChain); $key++) {
            $bridgeChannels[$key] = DirectChannel::create();
        }

        $customChannelResolver = InMemoryChannelResolver::createWithChanneResolver($channelResolver, $bridgeChannels);
        $firstMessageHandler = null;
        for ($key = 0; $key < count($messageHandlersToChain); $key++) {
            $messageHandlerBuilder = $messageHandlersToChain[$key];
            $nextHandlerKey = $key + 1;
            $previousHandlerKey = $key - 1;

            if ($this->hasNextHandler($messageHandlersToChain, $nextHandlerKey)) {
                $messageHandlerBuilder->withOutputMessageChannel((string)($nextHandlerKey));
            }
            if (!$this->hasNextHandler($messageHandlersToChain, $nextHandlerKey) && ($this->outputMessageChannelName)) {
                $messageHandlerBuilder = $messageHandlerBuilder
                                            ->withOutputMessageChannel($this->outputMessageChannelName);
            }

            $messageHandler = $messageHandlerBuilder->build($customChannelResolver, $referenceSearchService);

            if ($this->hasPreviousHandler($messageHandlersToChain, $previousHandlerKey)) {
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
     * @param array $messageHandlersToChain
     * @param $nextHandlerKey
     * @return bool
     */
    private function hasNextHandler(array $messageHandlersToChain, $nextHandlerKey): bool
    {
        return isset($messageHandlersToChain[$nextHandlerKey]);
    }

    /**
     * @param array $messageHandlersToChain
     * @param $previousHandlerKey
     * @return bool
     */
    private function hasPreviousHandler(array $messageHandlersToChain, $previousHandlerKey): bool
    {
        return isset($messageHandlersToChain[$previousHandlerKey]);
    }
}