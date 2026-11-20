<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowEdge
{
    /**
     * @param array<string, scalar|array<scalar>|null> $attributes
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly FlowEdgeKind $kind,
        public readonly array $attributes = [],
    ) {
    }

    /**
     * @param array{from: string, to: string, kind: string, attributes: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['from'], $data['to'], FlowEdgeKind::from($data['kind']), $data['attributes']);
    }

    public function identity(): string
    {
        return $this->kind->value . '|' . strlen($this->from) . '|' . $this->from . $this->to . '|' . ($this->kind === FlowEdgeKind::SENDS_TO ? $this->attributes['via'] ?? '' : '') . '|' . ($this->attributes['targetService'] ?? '') . '|' . ($this->attributes['targetChannel'] ?? '');
    }

    public function isInferred(): bool
    {
        return ($this->attributes['inferred'] ?? false) === true;
    }

    /**
     * @return array{from: string, to: string, kind: string, attributes: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['from' => $this->from, 'to' => $this->to, 'kind' => $this->kind->value, 'attributes' => $this->attributes];
    }
}
