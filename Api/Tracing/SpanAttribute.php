<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
final class SpanAttribute
{
    public const MESSAGING_SYSTEM = 'messaging.system';
    public const MESSAGING_OPERATION_TYPE = 'messaging.operation.type';
    public const MESSAGING_DESTINATION_NAME = 'messaging.destination.name';
    public const ENDPOINT_ID = 'ecotone.endpoint.id';
    public const PROJECTION_NAME = 'ecotone.projection.name';
    public const TRACE_ROOT = 'ecotone.trace.root';
    public const TRACE_CARRIED = 'ecotone.trace.carried';
    public const TRACE_UNTRACKED = 'ecotone.trace.untracked';
    public const CODE_FUNCTION_NAME = 'code.function.name';
    public const CHANNEL_NAME = 'ecotone.channel.name';
    public const BATCH_DELIVERIES = 'ecotone.batch.deliveries';

    public const ECOTONE = 'ecotone';
    public const OPERATION_SEND = 'send';
    public const OPERATION_PROCESS = 'process';
}
