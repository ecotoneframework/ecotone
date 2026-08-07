<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Attribute\WithoutMessageCollector;
use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\BatchSupportingMessageChannel;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
final class CollectorSenderInterceptor
{
    public function __construct(private CollectorStorage $collectorStorage, private string $targetChannel)
    {
    }

    public function send(
        MethodInvocation $methodInvocation,
        Message $message,
        #[Reference] ConfiguredMessagingSystem $configuredMessagingSystem,
        #[Reference] LoggingGateway $logger,
        ?WithoutMessageCollector $withoutMessageCollector = null,
    ): mixed {
        if ($withoutMessageCollector !== null) {
            return $methodInvocation->proceed();
        }
        /** For example Command Bus inside Command Bus */
        if ($this->collectorStorage->isEnabled()) {
            return $methodInvocation->proceed();
        }

        $this->collectorStorage->enable();
        try {
            $result = $methodInvocation->proceed();
            $collectedMessages = $this->collectorStorage->releaseMessages($logger, $message);
            if ($collectedMessages !== []) {
                $messageChannel = $this->getTargetChannel($configuredMessagingSystem);

                if ($this->supportsBatchMessages($messageChannel)) {
                    $messageChannel->send(
                        MessageBuilder::withPayload($this->combineIntoBatch($collectedMessages))->build()
                    );
                } else {
                    foreach ($collectedMessages as $collectedMessage) {
                        $messageChannel->send($collectedMessage);
                    }
                }
            }
        } finally {
            $this->collectorStorage->disable();
        }

        return $result;
    }

    private function getTargetChannel(ConfiguredMessagingSystem $configuredMessagingSystem): MessageChannel
    {
        return $configuredMessagingSystem->getMessageChannelByName($this->targetChannel);
    }

    private function supportsBatchMessages(MessageChannel $messageChannel): bool
    {
        if ($messageChannel instanceof MessageChannelInterceptorAdapter) {
            $messageChannel = $messageChannel->getInternalMessageChannel();
        }

        return $messageChannel instanceof BatchSupportingMessageChannel && $messageChannel->supportsBatchMessages();
    }

    /**
     * @param Message[] $collectedMessages
     */
    private function combineIntoBatch(array $collectedMessages): BatchMessage
    {
        $entries = [];
        foreach ($collectedMessages as $collectedMessage) {
            $payload = $collectedMessage->getPayload();
            if ($payload instanceof BatchMessage) {
                foreach ($payload->getEntries() as $entry) {
                    $entries[] = $entry;
                }

                continue;
            }

            $entries[] = ['payload' => $payload, 'headers' => $collectedMessage->getHeaders()->headers()];
        }

        return BatchMessage::fromEntries($entries);
    }
}
