<?php

namespace Fixture\Conversion;


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
}