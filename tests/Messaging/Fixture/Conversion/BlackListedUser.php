<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

/**
 * Interface BlackListedUser
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface BlackListedUser
{
    /**
     * @return Admin
     */
    public function bannedBy(): Admin;
}
