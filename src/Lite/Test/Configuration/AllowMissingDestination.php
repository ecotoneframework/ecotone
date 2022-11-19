<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

final class AllowMissingDestination
{
    public function invoke(MethodInvocation $methodInvocation)
    {
        try {
            return $methodInvocation->proceed();
        }catch (DestinationResolutionException) {
            return null;
        }
    }
}