<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow\Version;

use Ecotone\Messaging\Support\InvalidArgumentException;
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
final class PulseFlowVersionsNotConfigured
{
    public function __construct(private string $serviceName)
    {
    }

    public function list(?string $sourceService, ?string $queryId, PulseSnapshot $snapshot): string
    {
        $snapshot->authorizeRequestFrom($sourceService);

        return json_encode(['queryId' => PulseFlowVersions::queryIdOf($queryId, $this->serviceName), 'configured' => false, 'versions' => []], JSON_THROW_ON_ERROR);
    }

    public function version(string $payload, ?string $sourceService, ?string $queryId, PulseSnapshot $snapshot): string
    {
        $snapshot->authorizeRequestFrom($sourceService);

        throw InvalidArgumentException::create("Service {$this->serviceName} was asked by Pulse for a flow version, but it keeps none, because no location is configured there. Return FlowVersionsConfiguration::createWithLocation(__DIR__ . '/../flow-versions') from a #[ServiceContext] method of {$this->serviceName}, or stop sending flow version queries to this service.");
    }
}
