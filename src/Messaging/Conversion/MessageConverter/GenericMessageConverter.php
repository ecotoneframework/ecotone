<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\MessageConverter;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;

/**
 * Class GenericMessageConverter
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * An extension of the SimpleMessageConverter that uses a ConversionService to convert the payload of the message to the requested type.
 * Return null if the conversion service cannot convert from the payload type to the requested type.
 */
class GenericMessageConverter
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

}