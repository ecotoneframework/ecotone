<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Modelling\WithEvents;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class Appointment
{
    use WithEvents;

    #[Identifier]
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
    public static function create(CreateAppointmentCommand $command): self
    {
        return new self($command);
    }

    #[QueryHandler('getDuration')]
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @return string
     */
    public function getAppointmentId(): string
    {
        return $this->appointmentId;
    }
}
