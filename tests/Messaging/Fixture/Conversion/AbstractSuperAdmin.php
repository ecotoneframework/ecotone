<?php

namespace Ecotone\Tests\Messaging\Fixture\Conversion;

use Ecotone\Tests\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

/**
 * Class AbstractSuperAdmin
 * @package Ecotone\Tests\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class AbstractSuperAdmin implements Admin, Email
{
    public function getInformation() : self
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