<?php


namespace Ecotone\Tests\Messaging\Fixture\Conversion;

use Ecotone\Messaging\Attribute\IgnoreDocblockTypeHint;

#[IgnoreDocblockTypeHint]
interface IgnoreDocblockClassLevel
{
    /**
     * @return dsadasdsadosakd
     */
    public function doSomething() : array;
}