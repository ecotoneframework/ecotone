<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Conversion\ConversionService;
use SimplyCodedSoftware\IntegrationMessaging\Conversion\MediaType;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;
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
     * @var array|PropertyEditor[]
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
     * @var ReferenceSearchService
     */
    private $referenceSearchService;
    /**
     * @var ConversionService
     */
    private $conversionService;

    /**
     * InternalEnrichingService constructor.
     *
     * @param EnrichGateway|null $enrichGateway
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ReferenceSearchService $referenceSearchService
     * @param ConversionService $conversionService
     * @param PropertyEditor[] $setters
     * @param string $requestPayloadExpression
     * @param string[] $requestHeaders
     */
    public function __construct(?EnrichGateway $enrichGateway, ExpressionEvaluationService $expressionEvaluationService, ReferenceSearchService $referenceSearchService, ConversionService $conversionService, array $setters, ?string $requestPayloadExpression, array $requestHeaders)
    {
        $this->enrichGateway               = $enrichGateway;
        $this->setters                     = $setters;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->requestPayloadExpression    = $requestPayloadExpression;
        $this->requestHeaders              = $requestHeaders;
        $this->referenceSearchService = $referenceSearchService;
        $this->conversionService = $conversionService;
    }

    /**
     * @param Message $message
     * @return Message
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException
     */
    public function enrich(Message $message) : Message
    {
        $enrichedMessage = MessageBuilder::fromMessage($message)
                            ->build();
        $replyMessage = null;
        if ($this->enrichGateway) {
            $requestMessage = MessageBuilder::fromMessage($enrichedMessage);

            if ($this->requestPayloadExpression) {
                $requestPayload = $this->expressionEvaluationService->evaluate($this->requestPayloadExpression, [
                    "headers" => $enrichedMessage->getHeaders()->headers(),
                    "payload" => $enrichedMessage->getPayload(),
                    "referenceService" => $this->referenceSearchService
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