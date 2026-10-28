<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

use Composer\InstalledVersions;
use DateTimeInterface;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use Ecotone\Api\Messaging\Flow\FlowEdge;
use Ecotone\Api\Messaging\Flow\FlowEdgeKind;
use Ecotone\Api\Messaging\Flow\FlowNodeKind;
use Ecotone\Api\Messaging\Flow\MessageFlowGraph;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Throwable;

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
final class PulseSnapshot
{
    public const SCHEMA_VERSION = 1;
    public const ROUTING_KEY = 'ecotone.pulse.snapshot';
    public const REPLY_ROUTING_KEY = 'ecotone.pulse.snapshot_received';
    public const PROBE_BUDGET_IN_MILLISECONDS = 2000;
    public const CORE_KEYS = ['schemaVersion', 'serviceName', 'takenAt', 'overview', 'flowGraph', 'topology', 'channels', 'tenancy', 'tenant', 'sectionErrors'];

    /**
     * @param array<array{referenceName: string, commandMapping: array<string, string>, eventSubscriptions: array<array{channel: string, subscriptionKeys: string[], excludePublishingServices: string[], includePublishingServices: string[]}>, asynchronousChannel: string|null}> $serviceMaps
     * @param list<array{name: string, kind: string, probeGroup: string|null, destination: string|null, tenantScoped: bool}> $channels
     * @param array<string, PulseChannelProbe> $probes
     * @param array<string, PulseSnapshotSection> $tenantIndependentSections
     * @param array<string, PulseSnapshotSection> $tenantScopedSections
     * @param array{header: string, tenants: list<string>}|array{} $tenancy
     */
    public function __construct(
        private string $serviceName,
        private string $pulseServiceName,
        private string $framework,
        private array $serviceMaps,
        private array $channels,
        private array $probes,
        private array $tenantIndependentSections,
        private array $tenantScopedSections,
        private array $tenancy,
        private ConfiguredMessagingSystem $messagingSystem,
        private EcotoneClockInterface $clock,
        private PulseSectionIsolation $sectionIsolation,
    ) {
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function snapshotJson(?string $sourceService, array $headers): string
    {
        $this->authorizeRequestFrom($sourceService);

        $tenant = $this->isMultiTenant() && isset($headers[$this->tenancy['header']]) ? $this->advertisedTenant($headers[$this->tenancy['header']]) : null;

        return json_encode($tenant === null ? $this->snapshot() : $this->tenantSnapshot($tenant), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function tenantOfScopedRequest(array $headers): ?string
    {
        if (! $this->isMultiTenant() || ! isset($headers[$this->tenancy['header']])) {
            return null;
        }

        return $this->advertisedTenant($headers[$this->tenancy['header']]);
    }

    private function isMultiTenant(): bool
    {
        return $this->tenancy !== [];
    }

    private function advertisedTenant(mixed $tenant): string
    {
        if (! is_string($tenant) || ! in_array($tenant, $this->tenancy['tenants'], true)) {
            $named = is_scalar($tenant) ? (string) $tenant : get_debug_type($tenant);
            throw InvalidArgumentException::create("Service {$this->serviceName} was asked by Pulse for tenant `{$named}`, but it advertises only " . implode(', ', $this->tenancy['tenants']) . ", so the answer would come from the default connection and be shown as that tenant. Ask for one of the advertised tenants, or add `{$named}` to the tenant mapping of MultiTenantConfiguration.");
        }

        return $tenant;
    }

    /**
     * @return array<string, mixed>
     */
    private function tenantSnapshot(string $tenant): array
    {
        $snapshot = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'serviceName' => $this->serviceName,
            'takenAt' => $this->clock->now()->format(DateTimeInterface::ATOM),
            'tenant' => $tenant,
            'channels' => $this->channelsWithDepth(forTenant: true),
        ];

        $sectionErrors = [];
        foreach ($this->tenantScopedSections as $sectionName => $section) {
            $this->addSection($snapshot, $sectionErrors, $sectionName, $section);
        }

        return $this->withSectionErrors($snapshot, $sectionErrors);
    }

    /**
     * @return array{
     *     schemaVersion: int,
     *     serviceName: string,
     *     takenAt: string,
     *     overview: array{ecotoneVersion: string|null, phpVersion: string, framework: string, licence: array{enterprise: bool, expiresAt: string|null}},
     *     flowGraph: array<string, mixed>,
     *     topology: array{serviceMaps: array<mixed>},
     *     channels: list<array{name: string, kind: string, ready: int|null, delayed: int|null, inFlight: int|null, consumers: int|null, probeError: string|null, consumerToRun: string|null, handlers: list<string>, setUp: bool|null}>,
     * }&array<string, mixed>
     */
    private function snapshot(): array
    {
        $snapshot = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'serviceName' => $this->serviceName,
            'takenAt' => $this->clock->now()->format(DateTimeInterface::ATOM),
            'overview' => [
                'ecotoneVersion' => InstalledVersions::isInstalled('ecotone/ecotone') ? InstalledVersions::getPrettyVersion('ecotone/ecotone') : null,
                'phpVersion' => PHP_VERSION,
                'framework' => $this->framework,
                'licence' => ['enterprise' => true, 'expiresAt' => null],
            ],
            'flowGraph' => $this->messagingSystem->getMessageFlowGraph()->toArray(),
            'topology' => ['serviceMaps' => $this->serviceMaps],
            'channels' => $this->channelsWithDepth(forTenant: false),
        ];

        if ($this->isMultiTenant()) {
            $snapshot['tenancy'] = $this->tenancy;
        }

        $sectionErrors = [];
        foreach ($this->tenantIndependentSections as $sectionName => $section) {
            $this->addSection($snapshot, $sectionErrors, $sectionName, $section);
        }
        if (! $this->isMultiTenant()) {
            foreach ($this->tenantScopedSections as $sectionName => $section) {
                $this->addSection($snapshot, $sectionErrors, $sectionName, $section);
            }
        }

        return $this->withSectionErrors($snapshot, $sectionErrors);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, string> $sectionErrors
     */
    private function addSection(array &$snapshot, array &$sectionErrors, string $sectionName, PulseSnapshotSection $section): void
    {
        try {
            $snapshot[$sectionName] = [...($snapshot[$sectionName] ?? []), ...$this->sectionIsolation->readOnly($section->snapshot(...))];
        } catch (Throwable $exception) {
            $sectionErrors[$sectionName] = $exception->getMessage();
        }
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, string> $sectionErrors
     * @return array<string, mixed>
     */
    private function withSectionErrors(array $snapshot, array $sectionErrors): array
    {
        return $sectionErrors === [] ? $snapshot : [...$snapshot, 'sectionErrors' => $sectionErrors];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function channelsWithDepth(bool $forTenant): array
    {
        $measuredHere = fn (array $channel): bool => ! $this->isMultiTenant() || $channel['tenantScoped'] === $forTenant;
        $namesByGroup = [];
        foreach ($this->channels as $channel) {
            if ($channel['probeGroup'] !== null && $measuredHere($channel)) {
                $namesByGroup[$channel['probeGroup']][$channel['name']] = $channel['destination'];
            }
        }

        $depths = [];
        foreach ($namesByGroup as $group => $destinationByChannel) {
            $depths = [...$depths, ...$this->probe($this->probes[$group], $destinationByChannel)];
        }

        $consumers = $this->messagingSystem->list();
        $handlersByChannel = $this->handlersByChannel();
        $listed = $forTenant ? array_filter($this->channels, static fn (array $channel): bool => $channel['tenantScoped']) : $this->channels;

        return array_values(array_map(
            fn (array $channel): array => [
                'name' => $channel['name'],
                'kind' => $channel['kind'],
                'consumerToRun' => in_array($channel['name'], $consumers, true) ? $channel['name'] : null,
                'handlers' => $handlersByChannel[$channel['name']] ?? [],
                ...($depths[$channel['name']] ?? PulseChannelDepth::unmeasured())->toArray(),
            ],
            $listed
        ));
    }

    /**
     * @return array<string, list<string>>
     */
    private function handlersByChannel(): array
    {
        $graph = $this->messagingSystem->getMessageFlowGraph();
        $handlers = [];
        foreach ($graph->edgesOfKind(FlowEdgeKind::ASYNCHRONOUS) as $edge) {
            $channel = $graph->node($edge->from);
            if ($channel->kind === FlowNodeKind::CHANNEL) {
                $handlers[(string) $channel->attributes['name']][] = $this->handlerDescription($graph, $edge->to);
            }
        }

        return $handlers;
    }

    private function handlerDescription(MessageFlowGraph $graph, string $handlerId): string
    {
        $method = (string) $graph->node($handlerId)->attributes['method'];
        $messages = array_map(static fn (FlowEdge $edge): string => $graph->node($edge->from)->label, $graph->edgesOfKind(FlowEdgeKind::ROUTED_TO, to: $handlerId));

        return $messages === [] ? $method : implode(', ', array_unique($messages)) . ' → ' . $method;
    }

    /**
     * @param array<string, string> $destinationByChannel
     * @return array<string, PulseChannelDepth>
     */
    private function probe(PulseChannelProbe $probe, array $destinationByChannel): array
    {
        $startedAt = hrtime(true);
        try {
            $depths = $probe->depthsOf($destinationByChannel, self::PROBE_BUDGET_IN_MILLISECONDS);
        } catch (Throwable $exception) {
            return array_fill_keys(array_keys($destinationByChannel), PulseChannelDepth::failed($exception->getMessage()));
        }

        $tookInMilliseconds = intdiv(hrtime(true) - $startedAt, 1_000_000);
        if ($tookInMilliseconds > self::PROBE_BUDGET_IN_MILLISECONDS) {
            return array_fill_keys(array_keys($destinationByChannel), PulseChannelDepth::failed('Measuring the channel depth took ' . $tookInMilliseconds . ' ms, over the ' . self::PROBE_BUDGET_IN_MILLISECONDS . ' ms budget, so the result was dropped.'));
        }

        return $depths;
    }

    public function isRequestFromPulse(Message $message): bool
    {
        $headers = $message->getHeaders();

        return $headers->containsKey(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME)
            && $headers->get(DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME) === $this->pulseServiceName
            && $headers->containsKey(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY)
            && PulseRequest::isRoutedToPulse($headers->get(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY));
    }

    /**
     * @return array{payload: string, headers: array<string, mixed>}|null
     */
    public function failureReplyTo(Message $request, Throwable $cause): ?array
    {
        if (! $this->isRequestFromPulse($request)) {
            return null;
        }

        $headers = $request->getHeaders();
        $payload = json_encode([
            'requestKind' => $headers->get(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY),
            'serviceName' => $this->serviceName,
            'tenant' => $this->tenantRequestedBy($request),
            'message' => $cause->getMessage(),
            'queryId' => $headers->containsKey(PulseRequest::QUERY_ID_HEADER) ? $headers->get(PulseRequest::QUERY_ID_HEADER) : null,
            'tracingId' => $headers->containsKey(MessageHeaders::TRACING_ID) ? $headers->get(MessageHeaders::TRACING_ID) : null,
            'failedAt' => $this->clock->now()->format('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return [
            'payload' => $payload,
            'headers' => [
                ...($headers->containsKey(PulseRequest::QUERY_ID_HEADER) ? [PulseRequest::QUERY_ID_HEADER => $headers->get(PulseRequest::QUERY_ID_HEADER)] : []),
                MessageHeaders::CONTENT_TYPE => MediaType::TEXT_PLAIN,
                MessageHeaders::MESSAGE_CORRELATION_ID => $headers->get(MessageHeaders::MESSAGE_CORRELATION_ID),
                MessageHeaders::PARENT_MESSAGE_ID => $headers->get(MessageHeaders::MESSAGE_ID),
                MessageHeaders::ROUTING_SLIP => DistributedBusHeader::DISTRIBUTED_ROUTING_SLIP_VALUE,
                DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE => 'message',
                DistributedBusHeader::DISTRIBUTED_ROUTING_KEY => $headers->containsKey(DistributedBusHeader::DISTRIBUTED_REPLY_ROUTING_KEY) ? $headers->get(DistributedBusHeader::DISTRIBUTED_REPLY_ROUTING_KEY) : self::REPLY_ROUTING_KEY,
                DistributedBusHeader::DISTRIBUTED_SOURCE_SERVICE_NAME => $this->serviceName,
                DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME => $this->pulseServiceName,
            ],
        ];
    }

    private function tenantRequestedBy(Message $request): ?string
    {
        if (! $this->isMultiTenant() || ! $request->getHeaders()->containsKey($this->tenancy['header'])) {
            return null;
        }
        $tenant = $request->getHeaders()->get($this->tenancy['header']);

        return is_scalar($tenant) ? (string) $tenant : null;
    }

    public function authorizeRequestFrom(?string $sourceService): void
    {
        if ($sourceService === $this->pulseServiceName) {
            return;
        }

        $received = $sourceService ?? 'no service';
        throw InvalidArgumentException::create("Service {$this->serviceName} was asked for its Pulse snapshot by {$received}, but it only answers the configured Pulse service {$this->pulseServiceName}. Send the request from the Pulse service, or change the accepted service with PulseConfiguration::createWithDefaults(pulseServiceName: '{$received}') in a #[ServiceContext].");
    }
}
