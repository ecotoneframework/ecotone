<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertySetter;

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
     * @param string $name
     * @param string $value
     */
    public function __construct(string $name, string $value)
    {
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
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage)
    {
        $payload = $enrichMessage->getPayload();

        $payload[$this->name] = $this->value;

        return $payload;
    }
}