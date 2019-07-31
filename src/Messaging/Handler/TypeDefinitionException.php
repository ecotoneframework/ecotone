<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;
use Ecotone\Messaging\MessagingException;

/**
 * Class TypeDefinitionException
 * @package Ecotone\Messaging\Handler
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