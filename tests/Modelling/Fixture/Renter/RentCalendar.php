<?php
declare(strict_types=1);


namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class RentCalendar
 * @package Test\Ecotone\Modelling\Fixture\Renter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class RentCalendar
{
    private $calendarEvents = [];

    /**
     * @param AppointmentWasCreatedEvent $event
     * @EventHandler()
     */
    public function notify(AppointmentWasCreatedEvent $event) : void
    {
        $this->calendarEvents[$event->getAppointmentId()] = $event;
    }

    /**
     * @param string $appointmentIdToCheck
     * @return bool
     * @QueryHandler(inputChannelName="doesCalendarContainAppointments")
     */
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