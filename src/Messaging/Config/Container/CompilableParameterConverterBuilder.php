<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\InterfaceToCall;

interface CompilableParameterConverterBuilder
{
    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition|Reference;
}
