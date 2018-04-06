<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
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
     * @var string|null
     */
    private $requestPayloadExpression;
    /**
     * @var ExpressionEvaluationService
     */
    private $expressionEvaluationService;
    /**
     * @var string[]
     */
    private $requestHeaders;

    /**
     * InternalEnrichingService constructor.
     *
     * @param EnrichGateway|null          $enrichGateway
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param Setter[]                    $setters
     * @param string                      $requestPayloadExpression
     * @param string[]                    $requestHeaders
     */
    public function __construct(?EnrichGateway $enrichGateway, ExpressionEvaluationService $expressionEvaluationService, array $setters, ?string $requestPayloadExpression, array $requestHeaders)
    {
        $this->enrichGateway               = $enrichGateway;
        $this->setters                     = $setters;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->requestPayloadExpression    = $requestPayloadExpression;
        $this->requestHeaders              = $requestHeaders;
    }

    /**
     * @param Message $message
     * @return Message
     */
    public function enrich(Message $message) : Message
    {
        $enrichedMessage = MessageBuilder::fromMessage($message)
                            ->build();
        $replyMessage = $message;
        if ($this->enrichGateway) {
            $requestMessage = MessageBuilder::fromMessage($enrichedMessage);

            if ($this->requestPayloadExpression) {
                $requestPayload = $this->expressionEvaluationService->evaluate($this->requestPayloadExpression, [
                    "headers" => $enrichedMessage->getHeaders()->headers(),
                    "payload" => $enrichedMessage->getPayload()
                ]);

                $requestMessage->setPayload($requestPayload);
            }

            $extraHeaders = [];
            foreach ($this->requestHeaders as $requestHeaderName => $requestHeaderValue) {
                $extraHeaders[$requestHeaderName] = $requestHeaderValue;
            }
            $requestMessage->setMultipleHeaders($extraHeaders);

            $replyMessage = $this->enrichGateway->execute($requestMessage->build());
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