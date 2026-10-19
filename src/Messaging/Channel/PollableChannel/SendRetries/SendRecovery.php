<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel\SendRetries;

/**
 * licence Apache-2.0
 */
enum SendRecovery
{
    case Delivered;
    case SentToErrorChannel;
    case Failed;
}
