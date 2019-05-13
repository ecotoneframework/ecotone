<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Messaging\Handler\Gateway;


use SimplyCodedSoftware\Messaging\MessagingException;

/**
 * Class GatewayMessageConversionException
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
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