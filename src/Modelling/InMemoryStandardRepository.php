<?php
declare(strict_types=1);


namespace Ecotone\Modelling;


class InMemoryStandardRepository implements StandardRepository
{
    /**
     * @var object[]
     */
    private $aggregates;

    /**
     * InMemoryStandardRepository constructor.
     * @param object[] $aggregates
     */
    private function __construct(array $aggregates)
    {
        $this->aggregates = $aggregates;
    }


    public static function createEmpty() : self
    {
        return new self([]);
    }

    public static function createWith(array $aggregates) : self
    {
        return new self($aggregates);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return true;
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