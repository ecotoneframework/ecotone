<?php

namespace Ecotone\EventSourcing;

use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;
use Prooph\EventStore\Pdo\Projection\GapDetection;
use Prooph\EventStore\Pdo\Projection\PdoEventStoreReadModelProjector;

final class ProjectionSetupConfiguration
{
    /** @var ProjectionEventHandlerConfiguration[] */
    private array $projectionEventHandlers = [];
    /** @var array http://docs.getprooph.org/event-store/projections.html#Options https://github.com/prooph/pdo-event-store/pull/221/files */
    private array $projectionOptions;
    private bool $keepStateBetweenEvents = true;

    private function __construct(
        private string $projectionName,
        private ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration,
        private string $eventStoreReferenceName,
        private bool $withAllStreams,
        private array $streamNames,
        private array $categories
    ) {
        $this->projectionOptions = [
            PdoEventStoreReadModelProjector::OPTION_GAP_DETECTION => new GapDetection(),
            //            PdoEventStoreReadModelProjector::DEFAULT_LOCK_TIMEOUT_MS => 0,
            //            PdoEventStoreReadModelProjector::OPTION_UPDATE_LOCK_THRESHOLD => 0
        ];
    }

    public static function fromStream(string $projectionName, ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration, string $eventStoreReferenceName, string $streamName): static
    {
        return new static ($projectionName, $projectionLifeCycleConfiguration, $eventStoreReferenceName,false, [$streamName], []);
    }

    public static function fromStreams(string $projectionName, ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration, string $eventStoreReferenceName, string ...$streamNames): static
    {
        return new static($projectionName, $projectionLifeCycleConfiguration, $eventStoreReferenceName,false, $streamNames, []);
    }

    public static function fromCategory(string $projectionName, ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration, string $eventStoreReferenceName, string $name): static
    {
        return new static($projectionName, $projectionLifeCycleConfiguration, $eventStoreReferenceName,false, [], [$name]);
    }

    public static function fromCategories(string $projectionName, ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration, string $eventStoreReferenceName, string ...$names): static
    {
        return new static($projectionName, $projectionLifeCycleConfiguration, $eventStoreReferenceName,false, [], $names);
    }

    public static function fromAll(string $projectionName, string $eventStoreReferenceName, ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration): static
    {
        return new static($projectionName, $projectionLifeCycleConfiguration, $eventStoreReferenceName,true, [], []);
    }

    public function withKeepingStateBetweenEvents(bool $keepState): static
    {
        $this->keepStateBetweenEvents = $keepState;

        return $this;
    }

    public function isKeepingStateBetweenEvents(): bool
    {
        return $this->keepStateBetweenEvents;
    }

    public function withProjectionEventHandler(string $eventName, string $className, string $methodName, string $synchronousEventHandlerRequestChannel, string $asynchronousEventHandlerRequestChannel): static
    {
        Assert::keyNotExists($this->projectionEventHandlers, $eventName, "Projection {$this->projectionName} has incorrect configuration. Can't register event handler twice for the same event {$eventName}");

        $this->projectionEventHandlers[$eventName] = new ProjectionEventHandlerConfiguration($className, $methodName, $synchronousEventHandlerRequestChannel, $asynchronousEventHandlerRequestChannel);

        return $this;
    }

    public function getEventStoreReferenceName(): string
    {
        return $this->eventStoreReferenceName;
    }

    public function withOptions(array $options): static
    {
        $this->projectionOptions = $options;

        return $this;
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
    }

    public function isWithAllStreams(): bool
    {
        return $this->withAllStreams;
    }

    public function getStreamNames(): array
    {
        return $this->streamNames;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getProjectionLifeCycleConfiguration(): ProjectionLifeCycleConfiguration
    {
        return $this->projectionLifeCycleConfiguration;
    }

    public function getProjectionEventHandlers(): array
    {
        return $this->projectionEventHandlers;
    }

    public function getProjectionOptions(): array
    {
        return $this->projectionOptions;
    }

    /**
     * If projection is running in asynchronous mode, this channel allows to send
     * a message to trigger it to perform specific action
     */
    public function getTriggeringChannelName(): string
    {
        if ($this->getProjectionEventHandlers()) {
            /** @var ProjectionEventHandlerConfiguration $first */
            $first = reset($this->projectionEventHandlers);

            return $first->getTriggeringChannelName();
        }

        return NullableMessageChannel::CHANNEL_NAME;
    }

    public function getInitializationChannelName(): string
    {
        return $this->projectionLifeCycleConfiguration->getInitializationRequestChannel() ?? NullableMessageChannel::CHANNEL_NAME;
    }
}
