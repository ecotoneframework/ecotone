<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

/**
 * licence Apache-2.0
 */
enum SpanKind: string
{
    case Internal = 'internal';
    case Server = 'server';
    case Client = 'client';
    case Producer = 'producer';
    case Consumer = 'consumer';
}
