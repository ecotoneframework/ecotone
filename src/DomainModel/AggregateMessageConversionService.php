<?php

namespace SimplyCodedSoftware\DomainModel;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class AggregateMessageConversionService
 * @package SimplyCodedSoftware\DomainModel
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
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
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
            MediaType::createApplicationXPHPObjectWithTypeParameter($this->messageClassNameToConvertTo)
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
                                MediaType::createApplicationXPHPObjectWithTypeParameter($this->messageClassNameToConvertTo)
                    )
                )
                    ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter($this->messageClassNameToConvertTo))
                    ->build();
        }

        return $message;
    }
}