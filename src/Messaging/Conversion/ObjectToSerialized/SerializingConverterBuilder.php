<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\ObjectToSerialized;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;

/**
 * Class SerializingConverterBuilder
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SerializingConverterBuilder implements CompilableBuilder
{
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(SerializingConverter::class);
    }
}
