<?php

namespace Ecotone\EventSourcing\Config\InboundChannelAdapter;

use Ecotone\EventSourcing\ProjectionExecutor;
use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\EventSourcing\ProjectionSetupConfiguration;
use Ecotone\EventSourcing\ProjectionStatus;
use Ecotone\EventSourcing\Prooph\LazyProophProjectionManager;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Event;
use Prooph\EventStore\StreamName;

class ProjectionEventHandler
{
    public const PROJECTION_STATE            = 'projection.state';
    public const PROJECTION_IS_REBUILDING            = 'projection.is_rebuilding';
    public const PROJECTION_NAME             = 'projection.name';
    public const PROJECTION_IS_POLLING = 'projection.isPolling';

    private bool $wasInitialized = false;

    public function __construct(private LazyProophProjectionManager $lazyProophProjectionManager, private ProjectionSetupConfiguration $projectionSetupConfiguration, private ProjectionRunningConfiguration $projectionRunningConfiguration, private ConversionService $conversionService)
    {
    }

    public function beforeEventHandler(\Ecotone\Messaging\Message $message, MessagingEntrypointWithHeadersPropagation $messagingEntrypoint): ?\Ecotone\Messaging\Message
    {
        if ($this->shouldBePassedToEventHandler($message)) {
            return $message;
        }

        $this->execute($messagingEntrypoint);

        return null;
    }

    public function execute(MessagingEntrypointWithHeadersPropagation $messagingEntrypoint): void
    {
        if (! $this->wasInitialized && $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getInitializationRequestChannel()) {
            $messagingEntrypoint->send([], $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getInitializationRequestChannel());
            $this->wasInitialized = true;
        }

        $status = ProjectionStatus::RUNNING;
        $projectHasRelatedStream = $this->lazyProophProjectionManager->hasInitializedProjectionWithName($this->projectionSetupConfiguration->getProjectionName());
        if ($projectHasRelatedStream) {
            $status = $this->lazyProophProjectionManager->getProjectionStatus($this->projectionSetupConfiguration->getProjectionName());
        }

        $projectionExecutor = new class ($this->projectionSetupConfiguration, $this->conversionService, $messagingEntrypoint, $status) implements ProjectionExecutor {
            public function __construct(private ProjectionSetupConfiguration $projectionSetupConfiguration, private ConversionService $conversionService, private MessagingEntrypoint $messagingEntrypoint, private ProjectionStatus $projectionStatus)
            {
            }

            public function executeWith(string $eventName, Event $event, ?array $state = null): ?array
            {
                if (! isset($this->projectionSetupConfiguration->getProjectionEventHandlerConfigurations()[$eventName])) {
                    return $state;
                }

                $projectionEventHandler = $this->projectionSetupConfiguration->getProjectionEventHandlerConfigurations()[$eventName];
                $state = $this->messagingEntrypoint->sendWithHeaders(
                    $event->getPayload(),
                    array_merge(
                        $event->getMetadata(),
                        [
                            ProjectionEventHandler::PROJECTION_STATE => $state,
                            ProjectionEventHandler::PROJECTION_IS_REBUILDING => $this->projectionStatus === ProjectionStatus::REBUILDING,
                            ProjectionEventHandler::PROJECTION_NAME => $this->projectionSetupConfiguration->getProjectionName(),
                            ProjectionEventHandler::PROJECTION_IS_POLLING => true,
                        ]
                    ),
                    $projectionEventHandler->getSynchronousRequestChannelName()
                );

                if (! is_null($state)) {
                    $stateType = TypeDescriptor::createFromVariable($state);
                    if (! $stateType->isNonCollectionArray()) {
                        $state = $this->conversionService->convert(
                            $state,
                            $stateType,
                            MediaType::createApplicationXPHP(),
                            TypeDescriptor::createArrayType(),
                            MediaType::createApplicationXPHP()
                        );
                    }
                }

                return $this->projectionSetupConfiguration->isKeepingStateBetweenEvents() ? $state : null;
            }
        };

        if ($status === ProjectionStatus::REBUILDING && $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getRebuildRequestChannel()) {
            $messagingEntrypoint->send([], $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getRebuildRequestChannel());
        }

        if ($status === ProjectionStatus::DELETING && $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getDeleteRequestChannel()) {
            $messagingEntrypoint->send([], $this->projectionSetupConfiguration->getProjectionLifeCycleConfiguration()->getDeleteRequestChannel());
        }

        $this->lazyProophProjectionManager->run($this->projectionSetupConfiguration->getProjectionName(), $this->projectionSetupConfiguration->getProjectionStreamSource(), $projectionExecutor, array_keys($this->projectionSetupConfiguration->getProjectionEventHandlerConfigurations()), $this->projectionSetupConfiguration->getProjectionOptions());

        if ($status === ProjectionStatus::DELETING && $projectHasRelatedStream) {
            $projectionStreamName = new StreamName(LazyProophProjectionManager::getProjectionStreamName($this->projectionSetupConfiguration->getProjectionName()));
            if ($this->lazyProophProjectionManager->getLazyProophEventStore()->hasStream($projectionStreamName)) {
                $this->lazyProophProjectionManager->getLazyProophEventStore()->delete($projectionStreamName);
            }
        }
    }

    private function shouldBePassedToEventHandler(\Ecotone\Messaging\Message $message)
    {
        return $message->getHeaders()->containsKey(ProjectionEventHandler::PROJECTION_IS_POLLING)
            ? $message->getHeaders()->get(ProjectionEventHandler::PROJECTION_IS_POLLING)
            : false;
    }
}
