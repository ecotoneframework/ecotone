<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use Ecotone\Messaging\Message;

/**
 * licence Enterprise
 */
final class AdditionalContextResolver
{
    public static function resolve(Message $message, array $staticAdditionalContext, ?string $valueFromHeaderName, bool $valueFromPayload): array
    {
        $additionalContext = $staticAdditionalContext;
        if ($valueFromHeaderName !== null) {
            $additionalContext['value'] = $message->getHeaders()->containsKey($valueFromHeaderName) ? $message->getHeaders()->get($valueFromHeaderName) : null;
        } elseif ($valueFromPayload) {
            $additionalContext['value'] = $message->getPayload();
        }

        return $additionalContext;
    }
}
