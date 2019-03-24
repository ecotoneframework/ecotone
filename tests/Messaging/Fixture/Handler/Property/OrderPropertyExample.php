<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Property;

use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;
use stdClass as SomeClass;

/**
 * Class OrderPropertyExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Property
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