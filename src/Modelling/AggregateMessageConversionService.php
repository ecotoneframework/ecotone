<?php

namespace Ecotone\Modelling;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class AggregateMessageConversionService
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageConversionService
{
    /**
     * @var ConversionService
     */
    private $conversionService;
    /**
     * @var string
     */
    private $messageClassNameToConvertTo;

    /**
     * AggregateMessageConversionService constructor.
     * @param ConversionService $conversionService
     * @param string $messageClassNameToConvertTo
     */
    public function __construct(ConversionService $conversionService, string $messageClassNameToConvertTo)
    {
        $this->conversionService = $conversionService;
        $this->messageClassNameToConvertTo = $messageClassNameToConvertTo;
    }

    /**
     * @param Message $message
     * @return Message
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function convert(Message $message) : Message
    {
        if (!$message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)) {
            return $message;
        }

        $mediaType = MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE));
        if ($this->conversionService->canConvert(
            TypeDescriptor::createFromVariable($message->getPayload()),
            $mediaType,
            TypeDescriptor::create($this->messageClassNameToConvertTo),
            MediaType::createApplicationXPHPWithTypeParameter($this->messageClassNameToConvertTo)
        )) {
            return
                MessageBuilder::fromMessage($message)
                    ->setPayload(
                        $this->conversionService
                            ->convert(
                                $message->getPayload(),
                                TypeDescriptor::createFromVariable($message->getPayload()),
                                $mediaType,
                                TypeDescriptor::create($this->messageClassNameToConvertTo),
                                MediaType::createApplicationXPHPWithTypeParameter($this->messageClassNameToConvertTo)
                    )
                )
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($this->messageClassNameToConvertTo))
                    ->build();
        }

        return $message;
    }
}