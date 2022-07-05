<?php

namespace Tests\Ecotone\Messaging\Fixture\Conversion\Extra;

use Tests\Ecotone\Messaging\Fixture\Conversion\Admin;
use Tests\Ecotone\Messaging\Fixture\Conversion\BlackListedUser;

/**
 * Class LazyAdmin
 * @package Tests\Ecotone\Messaging\Fixture\Conversion\Extra
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