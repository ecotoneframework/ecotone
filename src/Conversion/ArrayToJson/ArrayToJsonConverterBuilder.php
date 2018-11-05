<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion\ArrayToJson;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\Converter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ArrayToJsonConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion\ArrayToJson
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
}