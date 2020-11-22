<?php


namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Ecotone\Messaging\Annotation\IgnoreDocblockTypeHint;

#[IgnoreDocblockTypeHint]
interface IgnoreDocblockClassLevel
{
    /**
     * @return dsadasdsadosakd
     */
    public function doSomething() : array;
}