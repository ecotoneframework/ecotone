<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\GetFavouriteTrait;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Extra\GetUserTrait;
use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Password as AdminPassword;

/**
 * Class SuperAdmin
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
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

    /**
     * @return $this
     */
    public function getUser()
    {

    }

    /**
     * @inheritDoc
     */
    public function getAdmin()
    {
        // TODO: Implement getAdmin() method.
    }

    /**
     * @return AdminPassword
     */
    public function getAdminPassword() : AdminPassword
    {

    }
}