<?php

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * This wrapper is used to add method calls
 * on top of defined object
 */
/**
 * licence Apache-2.0
 */
class DefinedObjectWrapper extends Definition
{
    public function __construct(private DefinedObject $instance)
    {
        $definition = $instance->getDefinition();
        parent::__construct($definition->className, $definition->arguments, $definition->factory);
    }
    public function instance(): DefinedObject
    {
        return $this->instance;
    }

}
