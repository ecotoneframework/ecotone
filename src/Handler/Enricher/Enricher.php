<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageProcessor;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class Enricher
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class Enricher implements MessageHandler
{
    /**
     * @var PropertySetter[]
     */
    private $propertySetters;

    /**
     * Enricher constructor.
     *
     * @param PropertySetter[] $propertySetters
     */
    public function __construct(array $propertySetters)
    {
        $this->propertySetters = $propertySetters;
    }

    /**
     * @inheritDoc
     */
    public function handle(Message $message): void
    {
        $newPayload = $message->getPayload();

        foreach ($this->propertySetters as $propertySetter) {

        }
    }
}