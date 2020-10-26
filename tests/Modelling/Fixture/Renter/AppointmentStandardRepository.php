<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\StandardRepository;

#[Repository]
class AppointmentStandardRepository implements StandardRepository
{
    /**
     * @var Appointment[]
     */
    private $appointments = [];

    private function __construct()
    {
    }

    public static function createEmpty() : self
    {
        return new self();
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
            if ($appointment->getAppointmentId() === $identifiers['appointmentId']) {
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