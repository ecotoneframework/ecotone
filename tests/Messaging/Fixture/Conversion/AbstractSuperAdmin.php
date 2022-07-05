<?php

namespace Tests\Ecotone\Messaging\Fixture\Conversion;

use Tests\Ecotone\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

/**
 * Class AbstractSuperAdmin
 * @package Tests\Ecotone\Messaging\Fixture\Conversion
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