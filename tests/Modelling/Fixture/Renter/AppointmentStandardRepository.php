<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Repository;
use Ecotone\Modelling\StandardRepository;

#[Repository]
class AppointmentStandardRepository implements StandardRepository
{
    /**
     * @var Appointment[]
     */
    private $appointments = [];

    private function __construct(array $appointments)
    {
        /** @var Appointment $appointment */
        foreach ($appointments as $appointment) {
            $this->appointments[$appointment->getAppointmentId()] = $appointment;
        }
    }

    public static function createEmpty() : self
    {
        return new self([]);
    }

    public static function createWith(array $appointments) : self
    {
        return new self($appointments);
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Appointment::class;
    }

    /**
     * @inheritDoc
     */
    public function findBy(string $aggregateClassName, array $identifiers) : ?object
    {
        foreach ($this->appointments as $appointment) {
            if ($appointment->getAppointmentId() == $identifiers['appointmentId']) {
                return $appointment;
            }
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function save(array $identifiers, object $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $this->appointments[$identifiers['appointmentId']] = $aggregate;
    }
}