<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

final class IdentifierMapper
{
    public function __construct(
        private array $mapping
    ) {

    }

    public function map(string $source): ?string
    {
        if (array_key_exists($source, $this->mapping)) {
            return $this->mapping[$source];
        }

        return null;
    }
}
