<?php
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\AggregateRepository;

/**
 * Class AppointmentCalendar
 * @package Test\Ecotone\Modelling\Fixture\Renter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @\Ecotone\Modelling\Annotation\AggregateRepository()
 */
class AppointmentRepository implements AggregateRepository
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
    public function findBy(string $aggregateClassName, array $identifiers)
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
    public function save(array $identifiers, $aggregate, array $metadata, ?int $expectedVersion): void
    {
        $this->appointments[$identifiers['appointmentId']] = $aggregate;
    }
}