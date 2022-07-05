<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorBuilder;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class StaticPropertySetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichPayloadWithValueBuilder implements PropertyEditorBuilder
{
    private string $propertyPath;
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
    public function build(ReferenceSearchService $referenceSearchService): PropertyEditor
    {
        return EnrichPayloadWithValuePropertyEditor::createWith(
            PropertyEditorAccessor::createWithMapping($referenceSearchService, ""),
            PropertyPath::createWith($this->propertyPath),
            $this->value
        );
    }
}