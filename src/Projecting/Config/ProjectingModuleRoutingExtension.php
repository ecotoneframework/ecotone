<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Config;

use Closure;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Polling;
use Ecotone\Api\Projecting\Projection;
use Ecotone\Api\Projecting\Streaming;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Modelling\Config\Routing\RoutingEvent;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;

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
        if ($isCommandOrEventHandler && $event->getRegistration()->hasAnnotation(Projection::class)) {
            /** @var Projection $projectionAttribute */
            $projectionAttribute = $event->getRegistration()->getClassAnnotationsWithType(Projection::class)[0];
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
