<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\Container\ContainerBuilder;

interface CompilerPass
{
    public function process(ContainerBuilder $builder): void;
}
