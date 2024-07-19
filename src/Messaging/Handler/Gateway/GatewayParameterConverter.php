<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gateway;

use Ecotone\Messaging\Handler\MethodArgument;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Interface ParameterDefinition
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface GatewayParameterConverter
{
    public function convertToMessage(?MethodArgument $methodArgument, MessageBuilder $messageBuilder): MessageBuilder;

    public function isSupporting(?MethodArgument $methodArgument): bool;
}
