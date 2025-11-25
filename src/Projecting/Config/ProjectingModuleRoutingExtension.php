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
use Ecotone\Projecting\Attribute\Projection;

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

            if ($projectionAttribute->isEventDriven()) {
                $event->setDestinationChannel($this->projectionTriggeringInputChannelFactory->__invoke($projectionAttribute->name));
            } else {
                $event->cancel();
            }
        }
    }
}
