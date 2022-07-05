<?php

namespace Ecotone\Tests\Messaging\Fixture\Conversion\Extra;

use Ecotone\Tests\Messaging\Fixture\Conversion\Admin;
use Ecotone\Tests\Messaging\Fixture\Conversion\BlackListedUser;

/**
 * Class LazyAdmin
 * @package Ecotone\Tests\Messaging\Fixture\Conversion\Extra
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