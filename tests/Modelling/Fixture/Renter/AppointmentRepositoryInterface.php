<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Renter;

use Ecotone\Modelling\Attribute\Repository;

interface AppointmentRepositoryInterface
{
    #[Repository]
    public function save(Appointment $appointment): void;

    #[Repository]
    public function find(string $appointmentId): ?Appointment;

    #[Repository]
    public function get(string $appointmentId): Appointment;
}
