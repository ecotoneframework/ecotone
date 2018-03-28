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
    private $setters;
    /**
     * @var EnrichGateway|null
     */
    private $enrichGateway;

    /**
     * InternalEnrichingService constructor.
     *
     * @param EnrichGateway|null $enrichGateway
     * @param Setter[]      $setters
     */
    public function __construct(?EnrichGateway $enrichGateway, array $setters)
    {
        $this->enrichGateway = $enrichGateway;
        $this->setters = $setters;
    }

    /**
     * @param Message $message
     * @return Message
     */
    public function enrich(Message $message) : Message
    {
        $enrichedMessage = MessageBuilder::fromMessage($message)
                            ->build();
        $replyMessage = null;
        if ($this->enrichGateway) {
            $replyMessage = $this->enrichGateway->execute($enrichedMessage);
        }

        foreach ($this->setters as $setter) {
            $settedMessage = MessageBuilder::fromMessage($enrichedMessage);
            if ($setter->isPayloadSetter()) {
                $settedMessage = $settedMessage
                    ->setPayload($setter->evaluate($enrichedMessage, $replyMessage));
            }else {
                foreach ($setter->evaluate($enrichedMessage, $replyMessage) as $headerName => $headerValue) {
                    $settedMessage = $settedMessage
                        ->setHeader($headerName, $headerValue);
                }
            }

            $enrichedMessage = $settedMessage->build();
        }

        return $enrichedMessage;
    }
}