<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\WithAggregateEvents;

#[Aggregate]
class Appointment
{
    use WithAggregateEvents;

    #[AggregateIdentifier]
    private $appointmentId;
    /**
     * @var int
     */
    private $duration;

    private function __construct(CreateAppointmentCommand $command)
    {
        $this->appointmentId = $command->getAppointmentId();
        $this->duration = $command->getDuration();

        $this->recordThat(new AppointmentWasCreatedEvent($command->getAppointmentId()));
    }

    #[CommandHandler]
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