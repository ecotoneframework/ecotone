<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class UuidToStringConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UuidToStringConverterBuilder implements ConverterBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return new UuidToStringConverter();
    }
}