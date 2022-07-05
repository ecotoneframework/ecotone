<?php

namespace Ecotone\Tests\Messaging\Fixture\Conversion;

use Ecotone\Tests\Messaging\Fixture\Conversion\Extra\GetFavouriteTrait;
use Ecotone\Tests\Messaging\Fixture\Conversion\Extra\GetUserTrait;
use Ecotone\Tests\Messaging\Fixture\Conversion\Password as AdminPassword;

/**
 * Class SuperAdmin
 * @package Ecotone\Tests\Messaging\Fixture\Conversion
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