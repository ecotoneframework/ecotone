<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\ArrayToJson;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ArrayToJsonConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion\ArrayToJson
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ArrayToJsonConverterBuilder implements ConverterBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return new ArrayToJsonConverter();
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}