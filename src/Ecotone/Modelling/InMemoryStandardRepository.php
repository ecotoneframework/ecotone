<?php
declare(strict_types=1);


namespace Ecotone\Modelling;


class InMemoryStandardRepository implements StandardRepository
{
    /**
     * @var object[]
     */
    private array $aggregates;
    private array $aggregateTypes;

    public function __construct(array $aggregates = [], array $aggregateTypes = [])
    {
        $this->aggregates = $aggregates;
        $this->aggregateTypes = $aggregateTypes;
    }


    public static function createEmpty() : self
    {
        /** @phpstan-ignore-next-line */
        return new static([], []);
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

        if (isset($this->aggregates[$aggregateClassName][$key])) {
            return $this->aggregates[$aggregateClassName][$key];
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $key = $this->getKey($identifiers);

        $this->aggregates[get_class($aggregate)][$key] = $aggregate;
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