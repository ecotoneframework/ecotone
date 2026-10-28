<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

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
 * @internal
 */
final class PulseRequest
{
    public const ROUTING_PREFIX = 'ecotone.pulse.';
    public const QUERY_ID_HEADER = 'ecotone_pulse_query_id';

    public static function isRoutedToPulse(mixed $routingKey): bool
    {
        return is_string($routingKey) && str_starts_with($routingKey, self::ROUTING_PREFIX);
    }
}
