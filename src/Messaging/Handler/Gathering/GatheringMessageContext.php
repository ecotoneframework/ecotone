<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Messaging\ErrorContext;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Handler\Recoverability\DelayedRetryErrorHandler;
use Ecotone\Messaging\Support\FrameworkHeaders;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 */
final class GatheringMessageContext
{
    public static function from(Message $message): array
    {
        $headers = FrameworkHeaders::unsetHeadersNotPropagatedToFollowingMessages($message->getHeaders()->headers());
        foreach ([DelayedRetryErrorHandler::ECOTONE_RETRY_HEADER, ErrorContext::DLQ_MESSAGE_REPLIED, ...ErrorContext::WHOLE_ERROR_CONTEXT] as $recoveryHeader) {
            unset($headers[$recoveryHeader]);
        }
        foreach ([MessageHeaders::MESSAGE_CORRELATION_ID, MessageHeaders::PARENT_MESSAGE_ID, MessageHeaders::TRACING_ID, MessageHeaders::TRACEPARENT, MessageHeaders::TRACESTATE] as $header) {
            if ($message->getHeaders()->containsKey($header)) {
                $headers[$header] = $message->getHeaders()->get($header);
            }
        }
        return $headers;
    }
}
