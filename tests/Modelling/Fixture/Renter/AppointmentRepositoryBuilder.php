<?php

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Modelling\EventSourcedRepository;
use Ecotone\Modelling\LazyRepositoryBuilder;
use Ecotone\Modelling\StandardRepository;

class AppointmentRepositoryBuilder implements LazyRepositoryBuilder
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

    public function isEventSourced(): bool
    {
        return false;
    }

    public function build(): EventSourcedRepository|StandardRepository
    {
        return AppointmentStandardRepository::createWith($this->appointments);
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(AppointmentStandardRepository::class, [$this->appointments], 'createWith');
    }
}
