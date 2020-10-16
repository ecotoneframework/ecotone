<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class InternalEnrichingService
 * @package Ecotone\Messaging\Handler\Enricher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
class InternalEnrichingService
{
    private array $setters;
    private ?\Ecotone\Messaging\Handler\Enricher\EnrichGateway $enrichGateway;
    private ?string $requestPayloadExpression;
    private \Ecotone\Messaging\Handler\ExpressionEvaluationService $expressionEvaluationService;
    /**
     * @var string[]
     */
    private array $requestHeaders;
    private \Ecotone\Messaging\Handler\ReferenceSearchService $referenceSearchService;
    private \Ecotone\Messaging\Conversion\ConversionService $conversionService;

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
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function enrich(Message $message) : Message
    {
        $replyMessage = null;
        if ($this->enrichGateway) {
            $requestMessage = MessageBuilder::fromMessage($message);

            if ($this->requestPayloadExpression) {
                $requestPayload = $this->expressionEvaluationService->evaluate($this->requestPayloadExpression, [
                        "headers" => $message->getHeaders()->headers(),
                        "payload" => $message->getPayload()
                    ],
                    $this->referenceSearchService
                );

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
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    private function getConvertedMessage(Message $message): \Ecotone\Messaging\Message
    {
        $enrichedMessage = MessageBuilder::fromMessage($message);

        if ($message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)) {
            $mediaType = MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE));
            if (!$mediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)) {
                if ($this->conversionService->canConvert(
                    $mediaType->hasTypeParameter() ? $mediaType->getTypeParameter() : TypeDescriptor::createFromVariable($message->getPayload()),
                    $mediaType,
                    TypeDescriptor::createArrayType(),
                    MediaType::createApplicationXPHP()
                )) {
                    $enrichedMessage = $enrichedMessage
                        ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY))
                        ->setPayload(
                            $this->conversionService->convert(
                                $message->getPayload(),
                                TypeDescriptor::createFromVariable($message->getPayload()),
                                $mediaType,
                                TypeDescriptor::createArrayType(),
                                MediaType::createApplicationXPHP()
                            )
                        );
                }
            }
        }

        return $enrichedMessage->build();
    }
}