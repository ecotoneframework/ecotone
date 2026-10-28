<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging;

use Ecotone\Messaging\MessagingException;

use function sprintf;

/**
 * licence Apache-2.0
 */
final class DestinationDoesNotExistException extends MessagingException
{
    private function __construct(
        string $message,
        public readonly string $channelName,
        public readonly string $destination,
        public readonly string $connection,
    ) {
        parent::__construct($message, self::DESTINATION_RESOLUTION_EXCEPTION);
    }

    public static function forChannel(string $channelName, string $destinationKind, string $destination, string $connection, string $builderCallToFixTheName): self
    {
        return new self(
            sprintf(
                "Channel '%s' requires its destination to exist, but %s '%s' does not exist on %s, so nothing was sent. Run the consumer of this channel (ecotone:run %s) or create the %s yourself, or fix the %s name (%s). To let this channel create the %s on send, remove withRequiredExistingDestination().",
                $channelName,
                $destinationKind,
                $destination,
                $connection,
                $channelName,
                $destinationKind,
                $destinationKind,
                $builderCallToFixTheName,
                $destinationKind,
            ),
            $channelName,
            $destination,
            $connection,
        );
    }
}
