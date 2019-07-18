<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\Renter;

use SimplyCodedSoftware\DomainModel\AggregateRepository;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class AppointmentCalendar
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\Renter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @\SimplyCodedSoftware\DomainModel\Annotation\AggregateRepository()
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
    public function findWithLockingBy(string $aggregateClassName, array $identifiers, int $expectedVersion)
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function save(Message $requestMessage, array $identifiers, $aggregate): void
    {
        $this->appointments[$identifiers['appointmentId']] = $aggregate;
    }
}