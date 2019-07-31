<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Conversion;

use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class ConversionServiceBuilder
 * @package Ecotone\Messaging\Conversion
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