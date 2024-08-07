<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Conversion\SerializedToObject;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;

/**
 * Class DeserializingConverterBuilder
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class DeserializingConverterBuilder implements CompilableBuilder
{
    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(DeserializingConverter::class);
    }
}
