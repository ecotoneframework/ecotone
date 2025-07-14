<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Defines how to handle failures when processing messages.
 * This is final failure strategy as it's used in case, when there is no other way to handle the failure.
 * For example, when there is no retry policy, or when the retry policy has reached its maximum number of attempts.
 * Also, when the destination of Error Channel is not defined, or sending to Error Channel fails.
 *
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
enum FinalFailureStrategy: string implements DefinedObject
{
    /**
     * Ignores the failed message - it will not be redelivered
     */
    case IGNORE = 'ignore';

    /**
     * Resends the failed message back to original Message Channel - it will be redelivered
     */
    case RESEND = 'resend';

    /**
     * Stop the consumer by rethrowing the exception
     */
    case STOP = 'stop';

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->value], 'from');
    }
}
