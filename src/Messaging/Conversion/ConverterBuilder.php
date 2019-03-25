<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Conversion;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class ConversionServiceBuilder
 * @package SimplyCodedSoftware\Messaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return Converter
     */
    public function build(ReferenceSearchService $referenceSearchService) : Converter;

    /**
     * @return string[]
     */
    public function getRequiredReferences() : array;
}