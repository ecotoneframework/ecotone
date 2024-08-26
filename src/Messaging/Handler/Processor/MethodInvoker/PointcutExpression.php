<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
interface PointcutExpression
{
    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool;
}
