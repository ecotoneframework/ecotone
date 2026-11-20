<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

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
final class ProjectionRun
{
    public function __construct(
        public readonly string $projectionName,
        public readonly string $operation,
        public readonly string $asyncChannelName,
        public readonly string $batchCompletionChannelName,
    ) {
    }

    public static function endpointIdFor(string $projectionName, string $operation): string
    {
        return $projectionName . '.' . $operation;
    }

    public static function gatherChannelFor(string $projectionName, string $operation): string
    {
        return 'ecotone.projection.' . $operation . '.completion:' . $projectionName;
    }

    public function attributeName(): string
    {
        return $this->operation === ProjectionOperationEndpoint::REBUILD ? '#[ProjectionRebuild]' : '#[ProjectionBackfill]';
    }

    public function endpointId(): string
    {
        return self::endpointIdFor($this->projectionName, $this->operation);
    }

    public function gatherChannelName(): string
    {
        return self::gatherChannelFor($this->projectionName, $this->operation);
    }
}
