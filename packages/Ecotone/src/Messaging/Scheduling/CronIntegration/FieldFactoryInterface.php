<?php

namespace Ecotone\Messaging\Scheduling\CronIntegration;

/**
 * @codeCoverageIgnore
 */
interface FieldFactoryInterface
{
    public function getField(int $position): FieldInterface;
}
