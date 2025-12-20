<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Closure;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Config\Routing\RoutingEvent;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;
use Ecotone\Projecting\Attribute\Polling;
use Ecotone\Projecting\Attribute\ProjectionV2;
use Ecotone\Projecting\Attribute\Streaming;

/**
 * This routing extension is responsible for changing destination channel to projection triggering channel
 */
class ProjectingModuleRoutingExtension implements RoutingEventHandler
{
    /**
     * @param Closure(string): string $projectionTriggeringInputChannelFactory
     */
    public function __construct(private Closure $projectionTriggeringInputChannelFactory)
    {
    }

    public function handleRoutingEvent(RoutingEvent $event, ?Configuration $messagingConfiguration = null): void
    {
        $registration = $event->getRegistration();
        $isCommandOrEventHandler = $registration->hasAnnotation(CommandHandler::class) || $registration->hasAnnotation(EventHandler::class);
        if ($isCommandOrEventHandler && $event->getRegistration()->hasAnnotation(ProjectionV2::class)) {
            /** @var ProjectionV2 $projectionAttribute */
            $projectionAttribute = $event->getRegistration()->getClassAnnotationsWithType(ProjectionV2::class)[0];
            $isPolling = $registration->hasAnnotation(Polling::class);
            $isEventStreaming = $registration->hasAnnotation(Streaming::class);

            // Event-driven projections (not polling and not event-streaming) should route to projection triggering channel
            if (! $isPolling && ! $isEventStreaming) {
                $event->setDestinationChannel($this->projectionTriggeringInputChannelFactory->__invoke($projectionAttribute->name));
            } else {
                $event->cancel();
            }
        }
    }
}
