<?php

namespace Ecotone\Messaging\Scheduling\CronIntegration;

/**
 * @codeCoverageIgnore
 */
/**
 * licence Apache-2.0
 */
interface FieldFactoryInterface
{
    public function getField(int $position): FieldInterface;
}
