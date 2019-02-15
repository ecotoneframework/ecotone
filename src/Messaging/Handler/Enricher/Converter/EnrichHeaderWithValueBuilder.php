<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\HeaderSetterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class StaticHeaderSetterBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher\Converter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EnrichHeaderWithValueBuilder implements PropertyEditorBuilder
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
    public function build(ReferenceSearchService $referenceSearchService): PropertyEditor
    {
        return EnrichHeaderWithValuePropertyEditor::create(
            PropertyEditorAccessor::createWithMapping($referenceSearchService, ""),
            PropertyPath::createWith($this->name),
            $this->value
        );
    }
}