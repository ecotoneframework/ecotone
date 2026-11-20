<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowEdgeChange
{
    public function __construct(public readonly FlowEdge $before, public readonly FlowEdge $after)
    {
    }

    /**
     * @return array{before: array{from: string, to: string, kind: string, attributes: array<string, mixed>}, after: array{from: string, to: string, kind: string, attributes: array<string, mixed>}}
     */
    public function toArray(): array
    {
        return ['before' => $this->before->toArray(), 'after' => $this->after->toArray()];
    }
}
