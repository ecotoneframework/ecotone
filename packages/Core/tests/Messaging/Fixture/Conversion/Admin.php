<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;


use Test\Ecotone\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

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