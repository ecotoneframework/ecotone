<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Closure;
use Ecotone\Api\Attribute\Collected;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Messaging\Handler\Type;
use ReflectionAttribute;
use ReflectionFunction;

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
 *
 */
final class CollectingPayloadType
{
    public static function for(Collected $collected): Type
    {
        if (! $collected->groupBy instanceof Closure) {
            return Type::anything();
        }
        foreach ((new ReflectionFunction($collected->groupBy))->getParameters() as $index => $parameter) {
            if ($parameter->getAttributes(Payload::class, ReflectionAttribute::IS_INSTANCEOF) !== [] || ($index === 0 && $parameter->getAttributes() === [])) {
                return Type::createWithDocBlock($parameter->getType() ? (string) $parameter->getType() : null, null);
            }
        }
        return Type::anything();
    }
}
