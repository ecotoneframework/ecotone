<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow;

use function json_decode;

/**
 * licence Apache-2.0
 */
final class AggregateIdMetadata
{
    /**
     * @param array<string, string> $identifiers
     */
    public function __construct(private array $identifiers)
    {

    }

    public static function createFrom(array|string|self $identifiers): self
    {
        if (! is_array($identifiers) && ! $identifiers instanceof self) {
            return new self(
                json_decode($identifiers, true, 512, JSON_THROW_ON_ERROR)
            );
        }

        if ($identifiers instanceof self) {
            return $identifiers;
        }

        return new self($identifiers);
    }

    /**
     * @return array<string, string>
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }
}
