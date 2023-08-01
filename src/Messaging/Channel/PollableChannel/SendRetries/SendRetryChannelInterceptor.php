<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

use Ecotone\Messaging\Channel\AbstractChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\MessageBuilder;
use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

final class SendRetryChannelInterceptor extends AbstractChannelInterceptor implements ChannelInterceptor
{
    public function __construct(
        private string $relatedChannel,
        private RetryTemplate $retryTemplate,
        private ?string $deadLetterChannel,
        private ConfiguredMessagingSystem $configuredMessagingSystem,
        private LoggerInterface $logger,
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
                $this->logger->info("Message was not sent to {$this->relatedChannel} due to exception. Will retry to send attempt: {$attempt}", [
                    'exception' => $exception->getMessage(),
                    'relatedChannel' => $this->relatedChannel,
                ]);

                try {
                    if ($this->retryTemplate->calculateNextDelay($attempt) > 0) {
                        usleep($this->retryTemplate->calculateNextDelay($attempt) * 1000);
                    }

                    $messageChannel->send($message);

                    return true;
                } catch (Exception $exception) {
                    $attempt++;
                }
            }
        }

        $this->logger->error("Message was not sent to {$this->relatedChannel} due to exception. No more retries will be done", [
            'exception' => $exception->getMessage(),
            'relatedChannel' => $this->relatedChannel,
        ]);

        if ($this->deadLetterChannel !== null) {
            $this->configuredMessagingSystem->getMessageChannelByName($this->deadLetterChannel)
                ->send(ErrorMessage::create(MessageDispatchingException::fromOtherException(
                    $exception,
                    MessageBuilder::fromMessage($message)
                        ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, $this->relatedChannel)
                        ->build()
                )));

            return true;
        }

        return false;
    }
}
