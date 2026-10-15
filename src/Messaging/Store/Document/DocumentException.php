<?php

namespace Ecotone\Messaging\Store\Document;

use Ecotone\Messaging\MessagingException;
use JsonException;

/**
 * licence Apache-2.0
 */
class DocumentException extends MessagingException
{
    public static function forStringThatIsNotJson(string $collectionName, string $documentId, JsonException $jsonException): self
    {
        return new self(
            sprintf(
                'Document %s cannot be stored in collection %s: it was given as a string that is not JSON (%s). '
                . 'A string document is stored as it is, so it has to be JSON already. '
                . 'Pass the array or object instead and it is converted to JSON for you, or json_encode() it before storing.',
                $documentId,
                $collectionName,
                $jsonException->getMessage(),
            ),
            self::errorCode(),
            $jsonException,
        );
    }

    protected static function errorCode(): int
    {
        return 2000;
    }
}
