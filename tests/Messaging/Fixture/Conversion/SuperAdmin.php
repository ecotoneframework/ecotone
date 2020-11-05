<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Test\Ecotone\Messaging\Fixture\Conversion\Extra\GetFavouriteTrait;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\GetUserTrait;
use Test\Ecotone\Messaging\Fixture\Conversion\Password as AdminPassword;

/**
 * Class SuperAdmin
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SuperAdmin extends AbstractSuperAdmin implements Admin
{
    use GetFavouriteTrait;
    use GetUserTrait;

    /**
     * @inheritDoc
     */
    public function getSuperAdmin()
    {
        // TODO: Implement getUser() method.
    }

    public function getUser(SuperAdmin $user) : SuperAdmin
    {

    }

    /**
     * @inheritDoc
     */
    public function getAdmin()
    {
        // TODO: Implement getAdmin() method.
    }

    public function getAdminPassword() : AdminPassword
    {

    }
}