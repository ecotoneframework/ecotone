<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Container\Compiler;

use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\FetchAggregateConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\FetchAggregateConverterBuilder;

use function is_array;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class VerifyEnterpriseLicenceForFetchedAggregates implements CompilerPass
{
    public function __construct(private bool $isRunningForEnterpriseLicence)
    {
    }

    public function process(ContainerBuilder $builder): void
    {
        if ($this->isRunningForEnterpriseLicence) {
            return;
        }

        foreach ($builder->getDefinitions() as $definition) {
            $this->verify($definition);
        }
    }

    private function verify(mixed $argument): void
    {
        if ($argument instanceof Definition) {
            if ($argument->getClassName() === FetchAggregateConverter::class) {
                throw FetchAggregateConverterBuilder::refusalWithoutEnterpriseLicence($argument);
            }

            $this->verify($argument->getArguments());
            foreach ($argument->getMethodCalls() as $methodCall) {
                $this->verify($methodCall->getArguments());
            }
        } elseif (is_array($argument)) {
            foreach ($argument as $value) {
                $this->verify($value);
            }
        }
    }
}
