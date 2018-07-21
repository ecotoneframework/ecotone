<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticPropertySetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichPayloadWithValueBuilder implements EnricherConverterBuilder
{
    /**
     * @var string
     */
    private $propertyPath;
    /**
     * @var mixed
     */
    private $value;

    /**
     * StaticPropertySetterBuilder constructor.
     *
     * @param string $propertyPath
     * @param mixed  $value
     */
    private function __construct(string $propertyPath, $value)
    {
        $this->propertyPath = $propertyPath;
        $this->value        = $value;
    }

    /**
     * @param string $propertyPath
     * @param mixed  $value
     *
     * @return EnrichPayloadWithValueBuilder
     */
    public static function createWith(string $propertyPath, $value) : self
    {
        return new self($propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): EnricherConverter
    {
        return EnrichPayloadWithValueConverter::createWith(PropertyPath::createWith($this->propertyPath), $this->value);
    }
}