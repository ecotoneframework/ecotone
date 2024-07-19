<?php

namespace Test\Ecotone\Modelling\Fixture\Renter;

/**
 * licence Apache-2.0
 */
class AppointmentWasCreatedEvent
{
    private $appointmentId;

    /**
     * AppointmentWasCreatedEvent constructor.
     * @param $appointmentId
     */
    public function __construct($appointmentId)
    {
        $this->appointmentId = $appointmentId;
    }

    /**
     * @return mixed
     */
    public function getAppointmentId()
    {
        return $this->appointmentId;
    }
}
