<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowNodeChange
{
    public function __construct(public readonly FlowNode $before, public readonly FlowNode $after)
    {
    }

    /**
     * @return array{before: array{id: string, kind: string, label: string, attributes: array<string, mixed>}, after: array{id: string, kind: string, label: string, attributes: array<string, mixed>}}
     */
    public function toArray(): array
    {
        return ['before' => $this->before->toArray(), 'after' => $this->after->toArray()];
    }
}
