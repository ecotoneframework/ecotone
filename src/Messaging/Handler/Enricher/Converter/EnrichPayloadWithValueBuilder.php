<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorBuilder;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;

/**
 * Class StaticPropertySetterBuilder
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
    public static function createWith(string $propertyPath, $value): self
    {
        return new self($propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function compile(MessagingContainerBuilder $builder): Definition
    {
        return new Definition(EnrichPayloadWithValuePropertyEditor::class, [
            new Reference(PropertyEditorAccessor::class),
            new Definition(PropertyPath::class, [$this->propertyPath], 'createWith'),
            $this->value,
        ]);
    }
}
