<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
final class AllowMissingDestination
{
    public function invoke(MethodInvocation $methodInvocation)
    {
        try {
            return $methodInvocation->proceed();
        } catch (DestinationResolutionException) {
            return;
        }
    }
}
