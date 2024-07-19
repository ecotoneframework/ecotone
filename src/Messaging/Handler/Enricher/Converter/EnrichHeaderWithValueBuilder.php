<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorBuilder;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;

/**
 * Class StaticHeaderSetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EnrichHeaderWithValueBuilder implements PropertyEditorBuilder
{
    private string $name;
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
    public static function create(string $name, $value): self
    {
        return new self($name, $value);
    }

    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(EnrichHeaderWithValuePropertyEditor::class, [
            new Definition(PropertyEditorAccessor::class, [new Reference(ExpressionEvaluationService::REFERENCE), ''], 'createWithMapping'),
            new Definition(PropertyPath::class, [$this->name], 'createWith'),
            $this->value,
        ], 'create');
    }
}
