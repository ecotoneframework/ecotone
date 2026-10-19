<?php

declare(strict_types=1);

namespace Ecotone\Api\Messaging\Flow;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class FlowNode
{
    /**
     * @param array<string, scalar|array<scalar>|null> $attributes
     */
    public function __construct(
        public readonly string $id,
        public readonly FlowNodeKind $kind,
        public readonly string $label,
        public readonly array $attributes = [],
    ) {
    }

    /**
     * @param array{id: string, kind: string, label: string, attributes: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['id'], FlowNodeKind::from($data['kind']), $data['label'], $data['attributes']);
    }

    /**
     * @param array<string, scalar|array<scalar>|null> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self($this->id, $this->kind, $this->label, array_merge($this->attributes, $attributes));
    }

    /**
     * @return array{id: string, kind: string, label: string, attributes: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['id' => $this->id, 'kind' => $this->kind->value, 'label' => $this->label, 'attributes' => $this->attributes];
    }
}
