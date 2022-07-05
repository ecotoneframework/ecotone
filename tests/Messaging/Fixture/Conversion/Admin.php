<?php

namespace Ecotone\Tests\Messaging\Fixture\Conversion;


use Ecotone\Tests\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

interface Admin
{
    /**
     * @return static
     */
    public function getSuperAdmin();

    /**
     * @return self
     */
    public function getAdmin();

    /**
     * @param mixed $password
     * @return mixed
     */
    public function getPassword(AdminPassword $password) : AdminPassword;
}