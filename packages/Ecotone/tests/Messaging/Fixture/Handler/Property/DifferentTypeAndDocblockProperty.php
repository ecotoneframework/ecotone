<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property;

use stdClass;

class DifferentTypeAndDocblockProperty
{
    /**
     * @var stdClass
     */
    private int $integer;

    /**
     * @var stdClass
     */
    private $unknown;
}
