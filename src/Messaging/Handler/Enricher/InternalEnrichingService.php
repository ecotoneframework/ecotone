<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Support\MessageBuilder;
use Throwable;

/**
 * Class InternalEnrichingService
 * @package Ecotone\Messaging\Handler\Enricher
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
class InternalEnrichingService
{
    private array $setters;
    private ?EnrichGateway $enrichGateway;
    private ?string $requestPayloadExpression;
    private ExpressionEvaluationService $expressionEvaluationService;
    /**
     * @var string[]
     */
    private array $requestHeaders;
    private ConversionService $conversionService;

    /**
     * InternalEnrichingService constructor.
     *
     * @param EnrichGateway|null $enrichGateway
     * @param ExpressionEvaluationService $expressionEvaluationService
     * @param ConversionService $conversionService
     * @param PropertyEditor[] $setters
     * @param string $requestPayloadExpression
     * @param string[] $requestHeaders
     */
    public function __construct(?EnrichGateway $enrichGateway, ExpressionEvaluationService $expressionEvaluationService, ConversionService $conversionService, array $setters, ?string $requestPayloadExpression, array $requestHeaders, private ExpressionLocation $requestPayloadLocation, private MessageBuilder $messageBuilder)
    {
        $this->enrichGateway               = $enrichGateway;
        $this->setters                     = $setters;
        $this->expressionEvaluationService = $expressionEvaluationService;
        $this->requestPayloadExpression    = $requestPayloadExpression;
        $this->requestHeaders              = $requestHeaders;
        $this->conversionService = $conversionService;
    }

    /**
     * @param Message $message
     * @return Message
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function enrich(Message $message): Message
    {
        $replyMessage = null;
        if ($this->enrichGateway) {
            $requestMessage = $this->messageBuilder->fromMessage($message);

            if ($this->requestPayloadExpression) {
                try {
                    $requestPayload = $this->expressionEvaluationService->evaluate(
                        $this->requestPayloadExpression,
                        [
                            'headers' => $message->getHeaders()->headers(),
                            'payload' => $message->getPayload(),
                        ],
                    );
                } catch (Throwable $exception) {
                    throw ExpressionEvaluationException::wrapping($this->requestPayloadLocation, $exception);
                }

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
            $settedMessage = $this->messageBuilder->fromMessage($enrichedMessage);

            if ($setter->isPayloadSetter()) {
                $settedMessage = $settedMessage
                    ->setPayload($setter->evaluate($enrichedMessage, $replyMessage));
            } else {
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
     * @throws \Ecotone\Messaging\Handler\TypeDefinitionException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    private function getConvertedMessage(Message $message): Message
    {
        $enrichedMessage = $this->messageBuilder->fromMessage($message);

        if ($message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)) {
            $mediaType = MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE));
            if (! $mediaType->isCompatibleWithParsed(MediaType::APPLICATION_X_PHP)) {
                if ($this->conversionService->canConvert(
                    $mediaType->hasTypeParameter() ? Type::create($mediaType->getParameter(MediaType::TYPE_PARAMETER)) : Type::createFromVariable($message->getPayload()),
                    $mediaType,
                    Type::array(),
                    MediaType::createApplicationXPHP()
                )) {
                    $enrichedMessage = $enrichedMessage
                        ->setContentType(MediaType::createApplicationXPHPWithTypeParameter('array'))
                        ->setPayload(
                            $this->conversionService->convert(
                                $message->getPayload(),
                                Type::createFromVariable($message->getPayload()),
                                $mediaType,
                                Type::array(),
                                MediaType::createApplicationXPHP()
                            )
                        );
                }
            }
        }

        return $enrichedMessage->build();
    }
}
