<?php
declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class GenericMessageConverter
 * @package Ecotone\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * An extension of the MessageConverter that uses a ConversionService to convert the payload of the message to the requested type.
 * Return null if the conversion service cannot convert from the payload type to the requested type.
 */
class GenericMessageConverter implements MessageConverter
{
    /**
     * @var ConversionService
     */
    private $conversionService;

    /**
     * GenericMessageConverter constructor.
     * @param ConversionService $conversionService
     */
    public function __construct(ConversionService $conversionService)
    {
        $this->conversionService = $conversionService;
    }

    /**
     * @inheritDoc
     */
    public function fromMessage(Message $message, Type $targetType)
    {

    }

    /**
     * @inheritDoc
     */
    public function toMessage($source, array $messageHeaders): ?MessageBuilder
    {
        if ($messageHeaders[MessageHeaders::CONTENT_TYPE]) {

        }
    }
}