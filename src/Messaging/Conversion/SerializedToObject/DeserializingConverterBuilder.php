<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\SerializedToObject;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class DeserializingConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DeserializingConverterBuilder implements ConverterBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return new DeserializingConverter();
    }
}