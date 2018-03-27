<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class InternalEnrichingService
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class InternalEnrichingService
{
    /**
     * @var array|Setter[]
     */
    private $propertySetters;
    /**
     * @var array|HeaderSetter[]
     */
    private $headerSetters;

    /**
     * InternalEnrichingService constructor.
     * @param Setter[] $propertySetters
     * @param HeaderSetter[] $headerSetters
     */
    public function __construct(array $propertySetters, array $headerSetters)
    {
        $this->propertySetters = $propertySetters;
        $this->headerSetters = $headerSetters;
    }

    /**
     * @param Message $message
     * @return Message
     */
    public function enrich(Message $message) : Message
    {
        $enrichedMessage = MessageBuilder::fromMessage($message)
                            ->build();
        foreach ($this->propertySetters as $propertySetter) {
            $enrichedMessage = MessageBuilder::fromMessage($enrichedMessage)
                                    ->setPayload($propertySetter->evaluate($enrichedMessage, null))
                                    ->build();
        }

        return $enrichedMessage;
    }
}