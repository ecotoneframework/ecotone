<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelService;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
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

        if ($exception !== null) {
            $attempt = 1;
            while ($this->retryTemplate->canBeCalledNextTime($attempt)) {
                $this->logger->info("Message was not sent to {$this->relatedChannel} due to exception. Trying to self-heal by doing retry attempt: {$attempt}/{$this->retryTemplate->getMaxAttempts()}. Exception message: `{$exception->getMessage()}`", [
                    'exception' => $exception->getMessage(),
                    'relatedChannel' => $this->relatedChannel,
                ]);

                try {
                    $this->clock->sleep($this->retryTemplate->durationToNextRetry($attempt));

                    $messageChannel->send($message);

                    return true;
                } catch (Exception $exception) {
                    $attempt++;
                }
            }
        }

        $this->logger->error("Message was not sent to {$this->relatedChannel} due to exception. No more retries will be done. Exception message: `{$exception->getMessage()}`", [
            'exception' => $exception->getMessage(),
            'relatedChannel' => $this->relatedChannel,
        ]);

        if ($this->deadLetterChannel !== null) {
            $this->errorChannelService->handle(
                $message,
                $exception,
                $this->configuredMessagingSystem->getMessageChannelByName($this->deadLetterChannel),
                $this->relatedChannel,
            );

            return true;
        }

        return false;
    }
}
