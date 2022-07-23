<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property;

use stdClass as SomeClass;

/**
 * Class OrderPropertyExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Property
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderPropertyExample
{
    private ?int $id;

    public $extendedName;

    #[PropertyAnnotationExample]
    protected static ?string $reference;

    #[PropertyAnnotationExample]
    private ?SomeClass $someClass;

    public function doSomething(): void
    {
    }
}
