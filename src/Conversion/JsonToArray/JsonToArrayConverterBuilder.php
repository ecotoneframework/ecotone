<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Conversion\JsonToArray;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\Converter;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class JsonToArrayConverterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Conversion\JsonToArray
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