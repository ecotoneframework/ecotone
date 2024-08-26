<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Pointcut;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\PointcutExpression;

/**
 * licence Apache-2.0
 */
class PointcutRegexExpression implements PointcutExpression
{
    public function __construct(private string $regex)
    {
        $this->regex = '#' . str_replace('*', '.*', $this->regex) . '#';
        $this->regex = str_replace('\\', '\\\\', $this->regex);
    }
    public function doesItCutWith(array $endpointAnnotations, InterfaceToCall $interfaceToCall): bool
    {
        return preg_match($this->regex, $interfaceToCall->getInterfaceName()) === 1;
    }
}
