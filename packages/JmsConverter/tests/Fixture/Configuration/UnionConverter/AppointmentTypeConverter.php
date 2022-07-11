<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter;

use Ecotone\Messaging\Attribute\Converter;

class AppointmentTypeConverter
{
    #[Converter]
    public function convertFrom(AppointmentType|StandardAppointmentType|TrialAppointmentType $type): string
    {
        return $type->getType();
    }

    #[Converter]
    public function convertTo(string $status): AppointmentType|StandardAppointmentType|TrialAppointmentType
    {
        if ($status === StandardAppointmentType::STANDARD) {
            return new StandardAppointmentType();
        } else {
            return new TrialAppointmentType();
        }
    }
}
