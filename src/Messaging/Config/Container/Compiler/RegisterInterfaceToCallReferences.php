<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\Container\AttributeReference;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\InterfaceParameterReference;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Handler\TypeResolver;

/**
 * licence Apache-2.0
 */
class RegisterInterfaceToCallReferences implements CompilerPass
{
    private TypeResolver $typeResolver;

    public function __construct()
    {
        $this->typeResolver = TypeResolver::create();
    }

    public function process(ContainerBuilder $builder): void
    {
        $this->registerAllReferences($builder->getDefinitions(), $builder);
    }

    private function registerAllReferences($argument, ContainerBuilder $containerBuilder): void
    {
        if ($argument instanceof DefinedObject) {
            $argument = $argument->getDefinition();
        }
        if (is_array($argument)) {
            foreach ($argument as $value) {
                $this->registerAllReferences($value, $containerBuilder);
            }
        } elseif ($argument instanceof Definition) {
            $this->registerAllReferences($argument->getArguments(), $containerBuilder);
            foreach ($argument->getMethodCalls() as $methodCall) {
                $this->registerAllReferences($methodCall->getArguments(), $containerBuilder);
            }
        } elseif ($argument instanceof InterfaceToCallReference) {
            if (! $containerBuilder->has($argument->getId())) {
                $this->typeResolver->registerInterfaceToCallDefinition($containerBuilder, $argument);
            }
        } elseif ($argument instanceof InterfaceParameterReference) {
            if (! $containerBuilder->has($argument->getId())) {
                $this->typeResolver->registerInterfaceToCallDefinition($containerBuilder, $argument->interfaceToCallReference());
            }
        } elseif ($argument instanceof AttributeReference) {
            if (! $containerBuilder->has($argument->getId())) {
                $this->typeResolver->registerAttribute($containerBuilder, $argument);
            }
        }
    }
}
