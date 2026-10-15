<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Api\Messaging\Message;

/**
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ParameterConverter
{
    /**
     * @return mixed
     */
    public function getArgumentFrom(Message $message);
}
