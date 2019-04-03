<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;


use Test\SimplyCodedSoftware\Messaging\Fixture\Conversion\TwoStepPassword as AdminPassword;

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