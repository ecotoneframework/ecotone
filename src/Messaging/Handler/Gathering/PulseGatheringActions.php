<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Gateway\GatheredGroups;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshot;
use JsonException;
use stdClass;

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
final class PulseGatheringActions
{
    public function __construct(private GatheredGroups $groups)
    {
    }

    public function requestRelease(string $payload, ?string $sourceService, array $headers, PulseSnapshot $snapshot): string
    {
        return $this->act(action: 'release', payload: $payload, sourceService: $sourceService, headers: $headers, snapshot: $snapshot);
    }

    public function discard(string $payload, ?string $sourceService, array $headers, PulseSnapshot $snapshot): string
    {
        return $this->act(action: 'discard', payload: $payload, sourceService: $sourceService, headers: $headers, snapshot: $snapshot);
    }

    private function act(string $action, string $payload, ?string $sourceService, array $headers, PulseSnapshot $snapshot): string
    {
        $snapshot->authorizeRequestFrom($sourceService);
        $snapshot->tenantOfScopedRequest($headers);
        $groupId = $this->groupIdFrom($payload);
        if ($action === 'release') {
            $this->groups->requestRelease($groupId);
            $status = 'release requested';
        } else {
            $this->groups->discard($groupId);
            $status = 'discarded';
        }

        return $snapshot->sectionsJson(sections: ['gathering'], actionResult: ['gatheringAction' => ['groupId' => $groupId, 'status' => $status]], sourceService: $sourceService, headers: $headers);
    }

    private function groupIdFrom(string $payload): string
    {
        try {
            $request = json_decode($payload, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $request = null;
        }
        if (! $request instanceof stdClass || array_keys(get_object_vars($request)) !== ['groupId'] || ! is_string($request->groupId) || trim($request->groupId) === '') {
            throw InvalidArgumentException::create('Pulse gathering actions require a JSON object containing only a non-empty string groupId. Send {"groupId":"id"}, taking id from gathering.groups in the Pulse snapshot.');
        }

        return $request->groupId;
    }
}
