<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion\JsonToArray;
use SimplyCodedSoftware\Messaging\Conversion\Converter;
use SimplyCodedSoftware\Messaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class JsonToArrayConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion\JsonToArray
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class JsonToArrayConverterBuilder implements ConverterBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return new JsonToArrayConverter();
    }
}