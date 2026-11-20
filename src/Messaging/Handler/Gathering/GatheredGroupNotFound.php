<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use RuntimeException;

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
final class GatheredGroupNotFound extends RuntimeException
{
    public static function forId(string $groupId): self
    {
        return new self("Gathering group '{$groupId}' was not found. Its closed-group retention may have expired, or the split's transaction has not committed yet. Increase GatheringConfiguration::withRetention(...) or replay failed parts before running ecotone:gathering:cleanup. For non-transactional part/report channels, send the split through an outbox / combined channel so parts are published with the commit, then replay the report.");
    }
}
