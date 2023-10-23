<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\UuidToString;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;

/**
 * Class UuidToStringConverterBuilder
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UuidToStringConverterBuilder implements CompilableBuilder
{
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(UuidToStringConverter::class);
    }
}
