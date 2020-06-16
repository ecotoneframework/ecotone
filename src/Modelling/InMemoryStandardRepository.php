<?php
declare(strict_types=1);


namespace Ecotone\Modelling;


class InMemoryStandardRepository implements StandardRepository
{
    /**
     * @var object[]
     */
    private $aggregates;
    private $aggregateTypes;

    public function __construct(array $aggregates = [], array $aggregateTypes = [])
    {
        $this->aggregates = $aggregates;
        $this->aggregateTypes = $aggregateTypes;
    }


    public static function createEmpty() : self
    {
        return new static([], []);
    }

    public static function createWith(array $aggregates) : self
    {
        return new static($aggregates);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return empty($this->aggregateTypes) ? true : in_array($aggregateClassName, $this->aggregateTypes);
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers): ?object
    {
        $key = $this->getKey($identifiers);

        if (isset($this->aggregates[$key])) {
            return $this->aggregates[$key];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $key = $this->getKey($identifiers);

        $this->aggregates[$key] = $aggregate;
    }

    private function getKey(array $identifiers) : string
    {
        $key = "";
        foreach ($identifiers as $identifier) {
            $key .= (string)$identifier;
        }

        return $key;
    }
}