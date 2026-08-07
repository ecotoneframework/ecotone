<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\AsyncPublishing;

use Ecotone\Messaging\MessagingException;

/**
 * licence Enterprise
 */
final class PublishingFailedException extends MessagingException
{
    /**
     * @param FailedDelivery[] $failedDeliveries
     */
    public static function withFailedDeliveries(array $failedDeliveries): self
    {
        $failureReasons = array_map(
            fn (FailedDelivery $failedDelivery) => $failedDelivery->getFailureReason(),
            $failedDeliveries,
        );

        $exception = new self(sprintf(
            'Failed to deliver %d published message(s): %s',
            count($failedDeliveries),
            implode('; ', array_unique($failureReasons)),
        ));
        $exception->failedDeliveries = $failedDeliveries;

        return $exception;
    }

    /** @var FailedDelivery[] */
    private array $failedDeliveries = [];

    public static function publisherNotConfiguredForAsyncPublishing(string $publisherReference): self
    {
        return new self(sprintf(
            'Message Publisher `%s` is not configured for asynchronous publishing. Enable async publishing on the publisher configuration to make use of asyncPublish.',
            $publisherReference,
        ));
    }

    /**
     * @return FailedDelivery[]
     */
    public function getFailedDeliveries(): array
    {
        return $this->failedDeliveries;
    }
}
