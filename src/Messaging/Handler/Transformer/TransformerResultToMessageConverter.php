<?php

namespace Ecotone\Messaging\Handler\Transformer;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\ResultToMessageConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class TransformerMessageProcessor
 * @package Messaging\Handler\Transformer
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class TransformerResultToMessageConverter implements ResultToMessageConverter
{
    public function __construct(private Type $returnType)
    {
    }

    /**
     * @inheritDoc
     */
    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        return match (true) {
            is_null($result) => null,
            $result instanceof Message => $result,
            is_array($result) => MessageBuilder::fromMessage($requestMessage)
                ->setMultipleHeaders($result)
                ->build(),
            default => MessageBuilder::fromMessage($requestMessage)
                ->setPayload($result)
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($this->returnType->toString()))
                ->build()
        };
    }
}
