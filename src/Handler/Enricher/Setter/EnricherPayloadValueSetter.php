<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class EnricherPayloadValueSetter implements Setter
{
    /**
     * @var DataSetter
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
     * @param DataSetter $payloadPropertySetter
     * @param PropertyPath     $propertyPath
     * @param mixed      $value
     */
    public function __construct(DataSetter $payloadPropertySetter, PropertyPath $propertyPath, $value)
    {
        $this->payloadPropertySetter = $payloadPropertySetter;
        $this->propertyPath          = $propertyPath;
        $this->value                 = $value;
    }

    /**
     * @param PropertyPath $propertyPath
     * @param mixed  $value
     *
     * @return self
     */
    public static function createWith(PropertyPath $propertyPath, $value) : self
    {
        return new self(DataSetter::create(), $propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        return $this->payloadPropertySetter->enrichDataWith($this->propertyPath, $enrichMessage->getPayload(), $this->value);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}