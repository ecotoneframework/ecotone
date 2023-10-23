<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;

/**
 * Interface MessageFromParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface GatewayParameterConverterBuilder extends CompilableBuilder
{
    public function isHandling(InterfaceParameter $parameter): bool;
}
