<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ConversionServiceBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConverterBuilder
{
    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return Converter
     */
    public function build(ReferenceSearchService $referenceSearchService) : Converter;
}