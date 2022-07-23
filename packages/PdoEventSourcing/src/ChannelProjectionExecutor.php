<?php

namespace Ecotone\EventSourcing;

use Ecotone\EventSourcing\Config\InboundChannelAdapter\ProjectionEventHandler;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\Event;

final class ChannelProjectionExecutor implements ProjectionExecutor
{
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
                    ProjectionEventHandler::PROJECTION_IS_REBUILDING => $this->projectionStatus == ProjectionStatus::REBUILDING(),
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
}
