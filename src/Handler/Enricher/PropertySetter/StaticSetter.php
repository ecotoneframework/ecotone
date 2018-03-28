<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticPropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class StaticSetter implements Setter
{
    /**
     * @var DataSetter
     */
    private $payloadPropertySetter;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $value;

    /**
     * StaticPropertySetterBuilder constructor.
     *
     * @param DataSetter $payloadPropertySetter
     * @param string     $name
     * @param string     $value
     */
    public function __construct(DataSetter $payloadPropertySetter, string $name, string $value)
    {
        $this->payloadPropertySetter = $payloadPropertySetter;
        $this->name  = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return self
     */
    public static function createWith(string $name, string $value) : self
    {
        return new self(DataSetter::create(), $name, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        return $this->payloadPropertySetter->enrichDataWith($this->name, $enrichMessage->getPayload(), $this->value);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return true;
    }
}