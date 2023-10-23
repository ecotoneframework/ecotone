<?php

namespace Ecotone\Messaging\Config\Container;

interface CompilableBuilder
{
    public function compile(MessagingContainerBuilder $builder): Definition|Reference;
}
