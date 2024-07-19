<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

/**
 * licence Apache-2.0
 */
interface ContainerImplementation extends CompilerPass
{
    public const RUNTIME_EXCEPTION_ON_INVALID_REFERENCE = 0;
    public const EXCEPTION_ON_INVALID_REFERENCE = 1;
    public const NULL_ON_INVALID_REFERENCE = 2;
}
