<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\MessageConverter;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;

class PsrHttpMessageConverter implements MessageConverter
{
    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, TypeDescriptor $targetType, HeaderMapper $headerMapper)
    {
        if (!$targetType->isClassOfType(ResponseInterface::class)) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, TypeDescriptor $sourceMediaType, array $messageHeaders, HeaderMapper $headerMapper): Message
    {
        if (!$sourceMediaType->isClassOfType(RequestInterface::class)) {
            return null;
        }
    }
}