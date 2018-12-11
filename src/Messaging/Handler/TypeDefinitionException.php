<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;
use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class TypeDefinitionException
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TypeDefinitionException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return 150;
    }
}