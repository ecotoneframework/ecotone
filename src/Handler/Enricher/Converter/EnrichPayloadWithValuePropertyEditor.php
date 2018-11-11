<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditorAccessor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyEditor;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Converter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnrichPayloadWithValuePropertyEditor implements PropertyEditor
{
    /**
     * @var PropertyEditorAccessor
     */
    private $payloadPropertySetter;
    /**
     * @var PropertyPath
     */
    private $propertyPath;
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
    public static function createWith(PropertyEditorAccessor $dataSetter, PropertyPath $propertyPath, $value) : self
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