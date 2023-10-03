<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\Messaging\Message;

/**
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ParameterConverter
{
    /**
     * @return mixed
     */
    public function getArgumentFrom(Message $message);
}
