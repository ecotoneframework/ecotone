<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticHeaderHeader
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\HeaderSetter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class StaticHeaderSetter implements HeaderSetter
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
    public function evaluate(Message $enrichedMessage, ?Message $replyMessage): array
    {
        return [
            $this->name => $this->value
        ];
    }
}