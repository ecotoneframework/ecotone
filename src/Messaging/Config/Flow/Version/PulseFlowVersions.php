<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow\Version;

use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseRequest;
use Ecotone\Modelling\MessageHandling\Distribution\Pulse\PulseSnapshot;

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
final class PulseFlowVersions
{
    public const LIST_ROUTING_KEY = 'ecotone.pulse.flow_versions.list';
    public const VERSION_ROUTING_KEY = 'ecotone.pulse.flow_versions.version';

    public function __construct(private FlowVersions $versions, private string $serviceName)
    {
    }

    public function list(?string $sourceService, ?string $queryId, PulseSnapshot $snapshot): string
    {
        $snapshot->authorizeRequestFrom($sourceService);

        return json_encode([
            'queryId' => self::queryIdOf($queryId, $this->serviceName),
            'configured' => true,
            'versions' => array_map(fn (FlowVersion $version): array => [
                'label' => $version->label,
                'storedAt' => $version->storedAt,
                'nodes' => count($version->graph->nodes()),
                'edges' => count($version->graph->edges()),
            ], $this->versions->all()),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public function version(string $payload, ?string $sourceService, ?string $queryId, PulseSnapshot $snapshot): string
    {
        $snapshot->authorizeRequestFrom($sourceService);
        $queryId = self::queryIdOf($queryId, $this->serviceName);
        $query = json_decode($payload, true);
        $label = is_array($query) ? $query['label'] ?? null : null;
        if (! is_string($label) || $label === '') {
            throw InvalidArgumentException::create("A Pulse flow version query needs a `label`, but `{$payload}` was sent. Send {\"label\": \"1.4.0\"}, taking the label from the flow version list of the service.");
        }
        $version = $this->versions->of($label);

        return json_encode([
            'queryId' => $queryId,
            'label' => $version->label,
            'storedAt' => $version->storedAt,
            'graph' => $version->graph->toArray(),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    public static function queryIdOf(?string $queryId, string $serviceName): string
    {
        if ($queryId === null || $queryId === '') {
            throw InvalidArgumentException::create("Service {$serviceName} was asked by Pulse for flow versions without the query id in header `" . PulseRequest::QUERY_ID_HEADER . "`, so Pulse could not match the result to its question. Send the request with metadata: ['" . PulseRequest::QUERY_ID_HEADER . "' => '<unique id>'] on DistributedBus::sendMessage().");
        }

        return $queryId;
    }
}
