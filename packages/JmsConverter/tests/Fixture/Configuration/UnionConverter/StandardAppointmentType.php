<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter;

class StandardAppointmentType implements AppointmentType
{
    public const STANDARD = 'standard';

    public function getType(): string
    {
        return self::STANDARD;
    }
}
