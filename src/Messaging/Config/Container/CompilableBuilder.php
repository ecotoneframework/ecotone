<?php

namespace Ecotone\Messaging\Config\Container;

/**
 * licence Apache-2.0
 */
interface CompilableBuilder
{
    public function compile(MessagingContainerBuilder $builder): Definition|Reference;
}
