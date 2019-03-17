<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\Password as AdminPassword;

/**
 * Class SuperAdmin
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SuperAdmin extends AbstractSuperAdmin implements Admin
{
    /**
     * @inheritDoc
     */
    public function getSuperAdmin()
    {
        // TODO: Implement getUser() method.
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