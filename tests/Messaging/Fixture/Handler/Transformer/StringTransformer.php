<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Transformer;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Class StringTransformer
 * @package Test\Ecotone\Messaging\Fixture\Handler\Transformer
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringTransformer implements DefinedObject
{
    public function transform(): string
    {
        return 'some';
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
