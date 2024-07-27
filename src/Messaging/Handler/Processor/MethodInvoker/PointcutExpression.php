<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker;

use Ecotone\Messaging\Handler\InterfaceToCall;

interface PointcutExpression
{
    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool;
}
