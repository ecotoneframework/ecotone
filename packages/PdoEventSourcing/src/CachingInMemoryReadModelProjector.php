<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing;

use Closure;
use Prooph\EventStore\Projection\ReadModel;
use Prooph\EventStore\Projection\ReadModelProjector;

class CachingInMemoryReadModelProjector implements ReadModelProjector
{
    private bool $isFromStreamSetup = false;
    private bool $isWhenAlreadySetup = false;

    public function __construct(private ReadModelProjector $inMemoryEventStoreReadModelProjector)
    {
    }

    public function init(Closure $callback): ReadModelProjector
    {
        $this->inMemoryEventStoreReadModelProjector->init($callback);

        return $this;
    }

    public function fromStream(string $streamName): ReadModelProjector
    {
        if ($this->isFromStreamSetup) {
            return $this;
        }
        $this->isFromStreamSetup = true;

        $this->inMemoryEventStoreReadModelProjector->fromStream($streamName);

        return $this;
    }

    public function fromStreams(string ...$streamNames): ReadModelProjector
    {
        if ($this->isFromStreamSetup) {
            return $this;
        }
        $this->isFromStreamSetup = true;

        $this->inMemoryEventStoreReadModelProjector->fromStreams(...$streamNames);

        return $this;
    }

    public function fromCategory(string $name): ReadModelProjector
    {
        if ($this->isFromStreamSetup) {
            return $this;
        }
        $this->isFromStreamSetup = true;

        $this->inMemoryEventStoreReadModelProjector->fromCategory($name);

        return $this;
    }

    public function fromCategories(string ...$names): ReadModelProjector
    {
        if ($this->isFromStreamSetup) {
            return $this;
        }
        $this->isFromStreamSetup = true;

        $this->inMemoryEventStoreReadModelProjector->fromCategories(...$names);

        return $this;
    }

    public function fromAll(): ReadModelProjector
    {
        if ($this->isFromStreamSetup) {
            return $this;
        }
        $this->isFromStreamSetup = true;

        $this->inMemoryEventStoreReadModelProjector->fromAll();

        return $this;
    }

    public function when(array $handlers): ReadModelProjector
    {
        if ($this->isWhenAlreadySetup) {
            return $this;
        }
        $this->isWhenAlreadySetup = true;

        $this->inMemoryEventStoreReadModelProjector->when($handlers);

        return $this;
    }

    public function whenAny(Closure $closure): ReadModelProjector
    {
        if ($this->isWhenAlreadySetup) {
            return $this;
        }
        $this->isWhenAlreadySetup = true;

        $this->inMemoryEventStoreReadModelProjector->whenAny($closure);

        return $this;
    }

    public function reset(): void
    {
        $this->inMemoryEventStoreReadModelProjector->reset();
    }

    public function stop(): void
    {
        $this->inMemoryEventStoreReadModelProjector->stop();
    }

    public function getState(): array
    {
        return $this->inMemoryEventStoreReadModelProjector->getState();
    }

    public function getName(): string
    {
        return $this->inMemoryEventStoreReadModelProjector->getName();
    }

    public function delete(bool $deleteProjection): void
    {
        $this->inMemoryEventStoreReadModelProjector->delete($deleteProjection);
    }

    public function run(bool $keepRunning = true): void
    {
        $this->inMemoryEventStoreReadModelProjector->run($keepRunning);
    }

    public function readModel(): ReadModel
    {
        return $this->inMemoryEventStoreReadModelProjector->readModel();
    }
}
