<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

/**
 * Interface BlackListedUser
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface BlackListedUser
{
    /**
     * @return Admin
     */
    public function bannedBy() : Admin;
}