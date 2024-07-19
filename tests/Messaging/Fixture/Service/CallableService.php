<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

/**
 * Interface CallableService
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface CallableService
{
    /**
     * @return bool
     */
    public function wasCalled(): bool;
}
