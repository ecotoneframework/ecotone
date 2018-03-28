<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticHeaderHeader
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class StaticHeaderSetter implements Setter
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
     * StaticHeaderSetter constructor.
     * @param string $name
     * @param string $value
     */
    private function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @param string $name
     * @param string $value
     * @return StaticHeaderSetter
     */
    public static function create(string $name, string $value) : self
    {
        return new self($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichedMessage, ?Message $replyMessage)
    {
        return [
            $this->name => $this->value
        ];
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return false;
    }
}