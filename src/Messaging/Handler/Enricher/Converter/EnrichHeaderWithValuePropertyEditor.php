<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\Messaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class StaticHeaderHeader
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher\Converter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichHeaderWithValuePropertyEditor implements PropertyEditor
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
     * @var PropertyEditorAccessor
     */
    private $dataSetter;

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
    public static function create(PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, $value) : self
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