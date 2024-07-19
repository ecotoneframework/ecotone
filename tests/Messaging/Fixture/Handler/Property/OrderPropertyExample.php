<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Property;

use stdClass as SomeClass;

/**
 * Class OrderPropertyExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Property
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class OrderPropertyExample
{
    private ?int $id;

    public $extendedName;

    #[PropertyAnnotationExample]
    protected static ?string $reference;

    #[PropertyAnnotationExampleBaseClasss]
    private ?SomeClass $someClass;

    public function doSomething(): void
    {
    }
}
