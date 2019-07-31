<?php


namespace Test\Ecotone\DomainModel\Fixture\Renter;

/**
 * Class CreateAppointmentCommand
 * @package Test\Ecotone\DomainModel\Fixture\Renter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CreateAppointmentCommand
{
    /**
     * @var string
     */
    private $appointmentId;
    /**
     * @var int
     */
    private $duration;

    /**
     * CreateAppointmentCommand constructor.
     * @param string $appointmentId
     * @param int $duration
     */
    public function __construct(string $appointmentId, int $duration)
    {
        $this->appointmentId = $appointmentId;
        $this->duration = $duration;
    }


    /**
     * @return string
     */
    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }
}