<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\MessageConverter;

use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class MessageConvertingException
 * @package SimplyCodedSoftware\Messaging\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageConvertingException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return 500;
    }
}