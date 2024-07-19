<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\Container\ContainerBuilder;

/**
 * licence Apache-2.0
 */
interface CompilerPass
{
    public function process(ContainerBuilder $builder): void;
}
