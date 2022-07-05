<?php

namespace Ecotone\Tests\Messaging\Fixture\Conversion;

/**
 * Interface BlackListedUser
 * @package Ecotone\Tests\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface BlackListedUser
{
    /**
     * @return Admin
     */
    public function bannedBy() : Admin;
}