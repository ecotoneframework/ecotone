<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Message;

/**
 * Class StaticPropertySetter
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class EnrichPayloadWithValuePropertyEditor implements PropertyEditor
{
    private PropertyEditorAccessor $payloadPropertySetter;
    private PropertyPath $propertyPath;
    /**
     * @var mixed
     */
    private $value;

    /**
     * StaticPropertySetterBuilder constructor.
     *
     * @param PropertyEditorAccessor $payloadPropertySetter
     * @param PropertyPath     $propertyPath
     * @param mixed      $value
     */
    public function __construct(PropertyEditorAccessor $payloadPropertySetter, PropertyPath $propertyPath, $value)
    {
        $this->payloadPropertySetter = $payloadPropertySetter;
        $this->propertyPath          = $propertyPath;
        $this->value                 = $value;
    }

    /**
     * @param PropertyEditorAccessor $dataSetter
     * @param PropertyPath $propertyPath
     * @param mixed $value
     *
     * @return self
     */
    public static function createWith(PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, $value): self
    {
        return new self($dataSetter, $propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        return $this->payloadPropertySetter->enrichDataWith($this->propertyPath, $enrichMessage->getPayload(), $this->value, $enrichMessage, $replyMessage);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}
