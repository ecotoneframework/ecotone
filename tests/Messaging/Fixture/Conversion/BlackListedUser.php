<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

/**
 * Interface BlackListedUser
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface BlackListedUser
{
    /**
     * @return Admin
     */
    public function bannedBy() : Admin;
}