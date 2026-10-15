<?php

declare(strict_types=1);

namespace Ecotone\Api\EventSourcing;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function is_array;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * licence Apache-2.0
 */
final class MetadataMatcher implements DefinedObject
{
    public function __construct(public readonly array $data = [])
    {
        foreach ($data as $item) {
            if (! ($item['fieldType'] instanceof FieldType)) {
                throw InvalidArgumentException::create('Field type must be an instance of FieldType');
            }
            if (! ($item['operator'] instanceof Operator)) {
                throw InvalidArgumentException::create('Operator must be an instance of Operator');
            }

            $this->validateValue($item['operator'], $item['value']);
        }
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->data], 'create');
    }

    public static function create(array $data): self
    {
        return new self($data);
    }

    public function withMetadataMatch(
        string $field,
        Operator $operator,
        $value,
        ?FieldType $fieldType = null
    ): self {
        if (null === $fieldType) {
            $fieldType = FieldType::METADATA;
        }

        return new self(...[...get_object_vars($this), 'data' => [...$this->data, ['field' => $field, 'operator' => $operator, 'value' => $value, 'fieldType' => $fieldType]]]);
    }

    /**
     * @param mixed $value
     */
    private function validateValue(Operator $operator, $value): void
    {
        if ($operator === Operator::IN || $operator === Operator::NOT_IN) {
            if (is_array($value)) {
                return;
            }

            throw new InvalidArgumentException(sprintf('Value must be an array for the operator %s.', $operator->name));
        }

        if ($operator === Operator::REGEX && ! is_string($value)) {
            throw new InvalidArgumentException('Value must be a string for the regex operator.');
        }

        if (! is_scalar($value)) {
            throw new InvalidArgumentException(sprintf('Value must have a scalar type for the operator %s.', $operator->name));
        }
    }
}
