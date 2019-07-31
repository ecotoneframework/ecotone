<?php
declare(strict_types=1);

namespace Test\Ecotone\DomainModel\Fixture\Renter;

use Ecotone\DomainModel\Annotation\Aggregate;
use Ecotone\DomainModel\Annotation\AggregateIdentifier;
use Ecotone\DomainModel\Annotation\CommandHandler;
use Ecotone\DomainModel\WithAggregateEvents;

/**
 * Class Appointment
 * @package Test\Ecotone\DomainModel\Fixture\Renter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class Appointment
{
    use WithAggregateEvents;

    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $appointmentId;
    /**
     * @var int
     */
    private $duration;

    private function __construct(CreateAppointmentCommand $command)
    {
        $this->appointmentId = $command->getAppointmentId();
        $this->duration = $command->getDuration();

        $this->record(new AppointmentWasCreatedEvent($command->getAppointmentId()));
    }

    /**
     * @param CreateAppointmentCommand $command
     * @return Appointment
     * @CommandHandler(inputChannelName="createAppointmentCommand")
     */
    public static function create(CreateAppointmentCommand $command) : self
    {
        return new self($command);
    }

    /**
     * @return string
     */
    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }
}