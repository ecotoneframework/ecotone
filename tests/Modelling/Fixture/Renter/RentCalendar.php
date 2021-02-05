<?php
declare(strict_types=1);


namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class RentCalendar
{
    private $calendarEvents = [];

    #[EventHandler]
    public function notify(AppointmentWasCreatedEvent $event) : void
    {
        $this->calendarEvents[$event->getAppointmentId()] = $event;
    }

    #[QueryHandler("doesCalendarContainAppointments")]
    public function getCalendarEvents(string $appointmentIdToCheck): bool
    {
        foreach ($this->calendarEvents as $appointmentId => $calendarEvent) {
            if ($appointmentId == $appointmentIdToCheck) {
                return true;
            }
        }

        return false;
    }
}