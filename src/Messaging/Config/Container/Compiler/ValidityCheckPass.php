<?php

namespace Ecotone\Messaging\Config\Container\Compiler;

use BackedEnum;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use InvalidArgumentException;

use function str_starts_with;

/**
 * licence Apache-2.0
 */
class ValidityCheckPass implements CompilerPass
{
    /**
     * @var Definition[]|Reference[]
     */
    private array $definitions;

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $builder): void
    {
        $this->definitions = $builder->getDefinitions();
        try {
            $this->resolveArgument($this->definitions);
        } finally {
            $this->definitions = [];
        }
    }

    private function resolveArgument(mixed $argument): void
    {
        if (is_array($argument)) {
            foreach ($argument as $value) {
                $this->resolveArgument($value);
            }
        } elseif ($argument instanceof Definition) {
            $this->resolveArgument($argument->getArguments());
            foreach ($argument->getMethodCalls() as $methodCall) {
                $this->resolveArgument($methodCall->getArguments());
            }
        } elseif ($argument instanceof Reference || $argument instanceof DefinedObject) {
            return;
        } elseif (is_object($argument) && ! $argument instanceof BackedEnum) {
            if (! str_starts_with(get_class($argument), 'Test\\')) {
                // We accept only not-dumpable instances from the 'Test\' namespace
                throw new InvalidArgumentException('Argument is not a self defined object: ' . get_class($argument));
            }
        }
    }
}
