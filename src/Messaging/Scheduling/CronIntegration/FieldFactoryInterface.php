<?php

namespace Ecotone\Messaging\Scheduling\CronIntegration;

/**
 * @codeCoverageIgnore
 */
/**
 * licence MIT
 */
interface FieldFactoryInterface
{
    public function getField(int $position): FieldInterface;
}
