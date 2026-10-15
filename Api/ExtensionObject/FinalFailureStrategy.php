<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 *
 * @link https://docs.ecotone.tech/modelling/recovering-tracing-and-monitoring/resiliency/final-failure-strategy#available-strategies
 */
enum FinalFailureStrategy: string implements DefinedObject
{
    case IGNORE = 'ignore';

    case RESEND = 'resend';

    case RELEASE = 'release';

    case STOP = 'stop';

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->value], 'from');
    }
}
