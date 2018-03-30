<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;

use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\DataSetter;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\PropertyPath;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter;
use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Class StaticHeaderHeader
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher\Setter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class StaticHeaderSetter implements Setter
{
    /**
     * @var string
     */
    private $propertyPath;
    /**
     * @var string
     */
    private $value;
    /**
     * @var DataSetter
     */
    private $dataSetter;

    /**
     * StaticHeaderSetter constructor.
     *
     * @param DataSetter   $dataSetter
     * @param PropertyPath $propertyPath
     * @param string       $value
     */
    private function __construct(DataSetter $dataSetter, PropertyPath $propertyPath, string $value)
    {
        $this->dataSetter = $dataSetter;
        $this->propertyPath = $propertyPath;
        $this->value        = $value;
    }

    /**
     * @param PropertyPath $propertyPath
     * @param string $value
     *
     * @return StaticHeaderSetter
     */
    public static function create(PropertyPath $propertyPath, string $value) : self
    {
        return new self(DataSetter::create(), $propertyPath, $value);
    }

    /**
     * @inheritDoc
     */
    public function evaluate(Message $enrichedMessage, ?Message $replyMessage)
    {
        return $this->dataSetter->enrichDataWith($this->propertyPath, $enrichedMessage->getHeaders()->headers(), $this->value);
    }

    /**
     * @inheritDoc
     */
    public function isPayloadSetter(): bool
    {
        return false;
    }
}