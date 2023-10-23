<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Config\Container\CompilableParameterConverterBuilder;

/**
 * Interface MethodParameterConverterBuilder
 * @package Ecotone\Messaging\Handler\Processor\MethodInvoker
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterConverterBuilder extends CompilableParameterConverterBuilder
{
    /**
     * @param InterfaceParameter $parameter
     * @return bool
     */
    public function isHandling(InterfaceParameter $parameter): bool;
}
