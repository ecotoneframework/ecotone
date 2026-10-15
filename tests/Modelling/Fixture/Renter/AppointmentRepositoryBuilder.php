<?php

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Modelling\RepositoryBuilder;

/**
 * licence Apache-2.0
 */
class AppointmentRepositoryBuilder implements RepositoryBuilder
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

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public static function createWith(array $appointments): self
    {
        return new self($appointments);
    }

    public function canHandle(string $aggregateClassName): bool
    {
        return $aggregateClassName === Appointment::class;
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(AppointmentStateStoredRepository::class, [$this->appointments], 'createWith');
    }
}
