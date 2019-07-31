<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property;

use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\Transaction\Transactional;
use stdClass as SomeClass;

/**
 * Class OrderPropertyExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Property
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderPropertyExample
{
    /**
     * This is example integer
     *
     * @var int
     */
    private $id;

    public $extendedName;

    /**
     * @var string
     * @PropertyAnnotationExample()
     */
    protected static $reference;
    /**
     * @var SomeClass
     */
    private $someClass;

    public function doSomething() : void
    {

    }
}