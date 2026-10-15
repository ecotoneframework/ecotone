<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function class_exists;

use Ecotone\Api\EventSourcing\Stream;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionResolver;

use function get_class;
use function is_object;

use ReflectionClass;
use ReflectionMethod;

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
final class DecisionModelStreamResolver
{
    public static function resolveFor(string|object $objectToInvokeOn, string $methodName): string
    {
        if (! class_exists(Stream::class)) {
            return AggregateDefinitionResolver::DEFAULT_STREAM;
        }

        $className = is_object($objectToInvokeOn) ? get_class($objectToInvokeOn) : $objectToInvokeOn;

        $methodReflection = new ReflectionMethod($className, $methodName);
        foreach ($methodReflection->getAttributes(Stream::class) as $attribute) {
            return $attribute->newInstance()->getTableName();
        }

        $classReflection = new ReflectionClass($className);
        foreach ($classReflection->getAttributes(Stream::class) as $attribute) {
            return $attribute->newInstance()->getTableName();
        }

        return AggregateDefinitionResolver::DEFAULT_STREAM;
    }
}
