<?php


namespace Ecotone\EventSourcing\Attribute;

use Ecotone\EventSourcing\EventStore;
use Ecotone\Messaging\Support\Assert;
use Enqueue\Dbal\DbalConnectionFactory;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Projection
{
    private string $name;
    private array $fromStreams;
    private array $options;
    private array|string $fromCategories;
    private bool $fromAll;
    private string $eventStoreReferenceName;

    public function __construct(string $name, string|array $fromStreams = [], string|array $fromCategories = [], bool $fromAll = false, array $options = [], string $eventStoreReferenceName = EventStore::class)
    {
        $fromStreams = is_string($fromStreams) ? [$fromStreams] : $fromStreams;
        $fromCategories = is_string($fromCategories) ? [$fromCategories] : $fromCategories;
        $countDefined = (int)$fromStreams + (int)$fromCategories + (int)$fromAll;
        Assert::isTrue($countDefined === 1, "Projection should be defined only with one of `fromStreams`, `fromCategories` or `fromALl`");

        $this->name = $name;
        $this->fromStreams = $fromStreams;
        $this->options = $options;
        $this->fromStreams = $fromStreams;
        $this->fromCategories = $fromCategories;
        $this->fromAll = $fromAll;
        $this->eventStoreReferenceName = $eventStoreReferenceName;
    }

    public function getEventStoreReferenceName(): string
    {
        return $this->eventStoreReferenceName;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFromStreams(): array
    {
        return $this->fromStreams;
    }

    public function getFromCategories(): array|string
    {
        return $this->fromCategories;
    }

    public function isFromAll(): bool
    {
        return $this->fromAll;
    }
}