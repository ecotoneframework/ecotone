<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\ArrayToJson;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;

/**
 * Class ArrayToJsonConverterBuilder
 * @package Ecotone\Messaging\Conversion\ArrayToJson
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ArrayToJsonConverterBuilder implements CompilableBuilder
{
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(ArrayToJsonConverter::class);
    }
}
