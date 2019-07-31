<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Test\Ecotone\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

/**
 * Class AbstractSuperAdmin
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AbstractSuperAdmin implements Admin, Email
{
    /**
     * @return self
     */
    public function getInformation()
    {

    }

    /**
     * @param AdminPassword $password
     * @return AdminPassword
     */
    public function getPassword(AdminPassword $password) : AdminPassword
    {

    }
}