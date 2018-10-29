<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\EnricherConverterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class StaticHeaderSetterBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichHeaderWithValueBuilder implements EnricherConverterBuilder
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var mixed
     */
    private $value;

    /**
     * StaticHeaderSetter constructor.
     * @param string $name
     * @param mixed $value
     */
    private function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return self
     */
    public static function create(string $name, $value) : self
    {
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): EnricherConverter
    {
        /** @var ExpressionEvaluationService $expressionEvaluationService */
        $expressionEvaluationService = $referenceSearchService->get(ExpressionEvaluationService::REFERENCE);

        return EnrichHeaderWithValueConverter::create(
            DataSetter::create($expressionEvaluationService, $referenceSearchService, ""),
            PropertyPath::createWith($this->name),
            $this->value
        );
    }
}