<?php

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
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Messaging\Handler\Type;

abstract class CustomConverter implements Converter
{
    public function __construct(private Type $sourceType, private Type $targetType)
    {
    }

    /**
     * @inheritDoc
     */
    public function matches(Type $sourceType, MediaType $sourceMediaType, Type $targetType, MediaType $targetMediaType): bool
    {
        return $sourceMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $targetMediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)
            && $sourceType->isCompatibleWith($this->sourceType)
            && (
                $targetType->equals($this->targetType)
                || ($targetType instanceof Type\UnionType && $targetType->containsType($this->targetType))
            );
    }
}
