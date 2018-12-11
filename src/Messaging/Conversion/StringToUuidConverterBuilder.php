<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class StringToUuidConverterBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StringToUuidConverterBuilder implements ConverterBuilder
{
    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): Converter
    {
        return new StringToUuidConverter();
    }
}