<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface GatewayParameterConverterBuilder
{
    public function isHandling(InterfaceParameter $parameter): bool;
    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition|Reference;
}
