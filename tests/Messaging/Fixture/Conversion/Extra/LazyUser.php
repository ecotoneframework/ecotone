<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion\Extra;

use Test\Ecotone\Messaging\Fixture\Conversion\Admin;
use Test\Ecotone\Messaging\Fixture\Conversion\BlackListedUser;

/**
 * Class LazyAdmin
 * @package Test\Ecotone\Messaging\Fixture\Conversion\Extra
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
