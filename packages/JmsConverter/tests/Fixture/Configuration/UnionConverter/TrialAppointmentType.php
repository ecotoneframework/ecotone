<?php

namespace Test\Ecotone\JMSConverter\Fixture\Configuration\UnionConverter;

class TrialAppointmentType implements AppointmentType
{
    public const TRIAL = 'trial';

    public function getType(): string
    {
        return self::TRIAL;
    }
}
