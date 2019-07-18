<?php
declare(strict_types=1);


namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Renter;

use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class RentCalendar
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Renter
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