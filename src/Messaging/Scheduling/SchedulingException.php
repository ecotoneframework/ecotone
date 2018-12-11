<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Scheduling;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class SchedulingException
 * @package SimplyCodedSoftware\Messaging\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SchedulingException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return 999;
    }
}