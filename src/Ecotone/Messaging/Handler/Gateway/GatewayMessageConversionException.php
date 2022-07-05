<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Handler\Gateway;


use Ecotone\Messaging\MessagingException;

/**
 * Class GatewayMessageConversionException
 * @package Ecotone\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayMessageConversionException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return 10000;
    }
}