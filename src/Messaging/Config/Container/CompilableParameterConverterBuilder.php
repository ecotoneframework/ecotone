<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
interface CompilableParameterConverterBuilder
{
    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition|Reference;
}
