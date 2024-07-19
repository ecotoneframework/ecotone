<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 05.04.18
 * Time: 09:50
 */

namespace Test\Ecotone\Messaging\Fixture\Handler\Transformer;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use stdClass;

/**
 * licence Apache-2.0
 */
class StdClassTransformer implements DefinedObject
{
    public function transform(): stdClass
    {
        return new stdClass();
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
