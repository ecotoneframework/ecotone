<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Conversion\MediaType;
use SimplyCodedSoftware\Messaging\Handler\ExpressionEvaluationService;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class InternalEnrichingService
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function enrich(Message $message) : Message
    {
        $replyMessage = null;
        if ($this->enrichGateway) {
            $requestMessage = MessageBuilder::fromMessage($message);

            if ($this->requestPayloadExpression) {
                $requestPayload = $this->expressionEvaluationService->evaluate($this->requestPayloadExpression, [
                    "headers" => $message->getHeaders()->headers(),
                    "payload" => $message->getPayload(),
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

            if ($replyMessage) {
                $replyMessage = $this->getConvertedMessage($replyMessage);
            }
        }

        $enrichedMessage = $this->getConvertedMessage($message);
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

    /**
     * @param Message $message
     * @return Message|MessageBuilder
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    private function getConvertedMessage(Message $message)
    {
        $enrichedMessage = MessageBuilder::fromMessage($message);

        if ($message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)) {
            $mediaType = MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE));
            if (!$mediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP_OBJECT)) {
                if ($this->conversionService->canConvert(
                    $mediaType->hasTypeParameter() ? TypeDescriptor::create($mediaType->getTypeParameter()) : TypeDescriptor::createFromVariable($message->getPayload()),
                    $mediaType,
                    TypeDescriptor::createArray(),
                    MediaType::createApplicationXPHPObject()
                )) {
                    $enrichedMessage = $enrichedMessage
                        ->setContentType(MediaType::createApplicationXPHPObjectWithTypeParameter(TypeDescriptor::ARRAY)->toString())
                        ->setPayload(
                            $this->conversionService->convert(
                                $message->getPayload(),
                                TypeDescriptor::createFromVariable($message->getPayload()),
                                $mediaType,
                                TypeDescriptor::createArray(),
                                MediaType::createApplicationXPHPObject()
                            )
                        );
                }
            }
        }

        return $enrichedMessage->build();
    }
}