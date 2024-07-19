<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher\Converter;

use Ecotone\Messaging\Handler\Enricher\PropertyEditor;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Message;

/**
 * Class StaticHeaderHeader
 * @package Ecotone\Messaging\Handler\Enricher\Converter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class EnrichHeaderWithValuePropertyEditor implements PropertyEditor
{
    private PropertyPath $propertyPath;
    /**
     * @var mixed
     */
    private $value;
    private PropertyEditorAccessor $dataSetter;

    /**
     * StaticHeaderSetter constructor.
     *
     * @param PropertyEditorAccessor   $dataSetter
     * @param PropertyPath $propertyPath
     * @param mixed       $value
     */
    private function __construct(PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, $value)
    {
        $this->dataSetter = $dataSetter;
        $this->propertyPath = $propertyPath;
        $this->value        = $value;
    }

    /**
     * @param PropertyEditorAccessor $dataSetter
     * @param PropertyPath $propertyPath
     * @param mixed $value
     *
     * @return EnrichHeaderWithValuePropertyEditor
     */
    public static function create(PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, $value): self
    {
        return new self($dataSetter, $propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichedMessage, ?Message $replyMessage)
    {
        return $this->dataSetter->enrichDataWith($this->propertyPath, $enrichedMessage->getHeaders()->headers(), $this->value, $enrichedMessage, $replyMessage);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return false;
    }
}
