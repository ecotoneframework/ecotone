<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * licence Apache-2.0
 */
class BoundParameterConverter implements CompilableBuilder
{
    public function __construct(
        private CompilableParameterConverterBuilder $parameterConverterBuilder,
        private InterfaceToCall $interfaceToCall,
    ) {
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return $this->parameterConverterBuilder->compile($this->interfaceToCall);
    }
}
