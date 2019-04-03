<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra;

use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Admin;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\BlackListedUser;

/**
 * Class LazyAdmin
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyUser implements BlackListedUser
{
    /**
     * @inheritDoc
     */
    public function bannedBy(): Admin
    {
        // TODO: Implement bannedBy() method.
    }
}