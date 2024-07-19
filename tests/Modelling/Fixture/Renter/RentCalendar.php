<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class RentCalendar
{
    private $calendarEvents = [];

    #[EventHandler]
    public function notify(AppointmentWasCreatedEvent $event): void
    {
        $this->calendarEvents[$event->getAppointmentId()] = $event;
    }

    #[QueryHandler('doesCalendarContainAppointments')]
    public function getCalendarEvents($appointmentIdToCheck): bool
    {
        foreach ($this->calendarEvents as $appointmentId => $calendarEvent) {
            if ($appointmentId == $appointmentIdToCheck) {
                return true;
            }
        }

        return false;
    }
}
