<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\Messaging\BatchMessage;
use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\AsyncPublishing\FailedDelivery;
use Ecotone\Messaging\Channel\AsyncPublishing\PublishingFailedException;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Ecotone\Messaging\Support\MessageBuilder;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * licence Apache-2.0
 */
final class SendRetryChannelInterceptor extends AbstractChannelInterceptor implements ChannelInterceptor
{
    public function __construct(
        private string $relatedChannel,
        private RetryTemplate $retryTemplate,
        private ?string $deadLetterChannel,
        private ErrorChannelService $errorChannelService,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
        private LoggerInterface $logger,
        private EcotoneClockInterface $clock
    ) {
    }

    public function afterSendCompletion(Message $message, MessageChannel $messageChannel, ?Throwable $exception): bool
    {
        if ($exception === null) {
            return false;
        }

        $messageToRedeliver = $this->messageContainingOnlyFailedDeliveries($message, $exception);

        if ($exception !== null) {
            $attempt = 1;
            while ($this->retryTemplate->canBeCalledNextTime($attempt)) {
                $this->logger->info("Message was not sent to {$this->relatedChannel} due to exception. Trying to self-heal by doing retry attempt: {$attempt}/{$this->retryTemplate->getMaxAttempts()}. Exception message: `{$exception->getMessage()}`", [
                    'exception' => $exception->getMessage(),
                    'relatedChannel' => $this->relatedChannel,
                ]);

                try {
                    $this->clock->sleep($this->retryTemplate->durationToNextRetry($attempt));

                    $messageChannel->send($messageToRedeliver);

                    return true;
                } catch (Exception $exception) {
                    $messageToRedeliver = $this->messageContainingOnlyFailedDeliveries($messageToRedeliver, $exception);
                    $attempt++;
                }
            }
        }

        $this->logger->error("Message was not sent to {$this->relatedChannel} due to exception. No more retries will be done. Exception message: `{$exception->getMessage()}`", [
            'exception' => $exception->getMessage(),
            'relatedChannel' => $this->relatedChannel,
        ]);

        if ($this->deadLetterChannel !== null) {
            $deadLetterChannel = $this->configuredMessagingSystem->getMessageChannelByName($this->deadLetterChannel);
            foreach ($this->unpackMessages($messageToRedeliver) as $failedMessage) {
                $this->errorChannelService->handle(
                    $failedMessage,
                    $exception,
                    $deadLetterChannel,
                    $this->relatedChannel,
                );
            }

            return true;
        }

        return false;
    }

    /**
     * @return Message[]
     */
    private function unpackMessages(Message $message): array
    {
        $payload = $message->getPayload();
        if (! $payload instanceof BatchMessage) {
            return [$message];
        }

        return array_map(
            static fn (array $entry): Message => MessageBuilder::withPayload($entry['payload'])
                ->setMultipleHeaders($entry['headers'])
                ->build(),
            $payload->getEntries(),
        );
    }

    private function messageContainingOnlyFailedDeliveries(Message $message, Throwable $exception): Message
    {
        if (! $exception instanceof PublishingFailedException || ! $message->getPayload() instanceof BatchMessage) {
            return $message;
        }

        $failedDeliveries = $exception->getFailedDeliveries();
        if ($failedDeliveries === []) {
            return $message;
        }

        $batchOfFailedDeliveries = BatchMessage::fromEntries(array_map(
            static fn (FailedDelivery $failedDelivery): array => [
                'payload' => $failedDelivery->getMessage()->getPayload(),
                'headers' => $failedDelivery->getMessage()->getHeaders()->headers(),
            ],
            $failedDeliveries,
        ));

        return MessageBuilder::fromMessage($message)
            ->setPayload($batchOfFailedDeliveries)
            ->build();
    }
}
