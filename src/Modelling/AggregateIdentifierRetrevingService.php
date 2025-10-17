<?php

namespace Ecotone\Modelling;

use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\AggregateIdMetadata;

/**
 * Class AggregateMessageConversionService
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AggregateIdentifierRetrevingService implements MessageProcessor
{
    public function __construct(
        private string $aggregateClassName,
        private ConversionService $conversionService,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private Type $typeToConvertTo,
        private array $metadataIdentifierMapping,
        private array $messageIdentifierMapping,
        private array $identifierMapping,
        private ExpressionEvaluationService $expressionEvaluationService,
    ) {

    }

    public function process(Message $message): Message
    {
        /** @TODO Ecotone 2.0 (remove) this. For backward compatibility because it's ran again when message is consumed from Queue e*/
        if ($this->messageContainsCorrectAggregateId($message)) {
            return $message;
        }

        if ($message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)) {
            $aggregateIds = $message->getHeaders()->get(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER);
            $aggregateIds = is_array($aggregateIds) ? $aggregateIds : [array_key_first($this->messageIdentifierMapping) => $aggregateIds];

            return MessageBuilder::fromMessage($message)
                ->setHeader(AggregateMessage::AGGREGATE_ID, AggregateIdResolver::resolveArrayOfIdentifiers($this->aggregateClassName, $aggregateIds))
                ->removeHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)
                ->build();
        }

        $payload   = $message->getPayload();
        $mediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($payload)->toString());
        if ($this->conversionService->canConvert(
            Type::createFromVariable($payload),
            $mediaType,
            $this->typeToConvertTo,
            MediaType::createApplicationXPHPWithTypeParameter($this->typeToConvertTo->toString())
        )) {
            $payload = $this->conversionService
                ->convert(
                    $payload,
                    Type::createFromVariable($payload),
                    $mediaType,
                    $this->typeToConvertTo,
                    MediaType::createApplicationXPHPWithTypeParameter($this->typeToConvertTo->toString())
                );
        }

        $aggregateIdentifiers = [];
        foreach ($this->messageIdentifierMapping as $aggregateIdentifierName => $aggregateIdentifierMappingName) {
            if (is_null($aggregateIdentifierMappingName)) {
                $aggregateIdentifiers[$aggregateIdentifierName] = null;
                continue;
            }

            $payload = ! is_object($payload) && $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE)
                ? $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE)
                : $payload;
            $aggregateIdentifiers[$aggregateIdentifierName] =
                $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $payload)
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $payload)
                    : null;
        }
        $metadata = $message->getHeaders()->headers();
        foreach ($this->metadataIdentifierMapping as $identifierName => $headerName) {
            if (array_key_exists($headerName, $metadata)) {
                $aggregateIdentifiers[$identifierName] = $metadata[$headerName];
            }
        }
        foreach ($this->identifierMapping as $identifierName => $expression) {
            $aggregateIdentifiers[$identifierName] = $this->expressionEvaluationService->evaluate($expression, [
                'headers' => $metadata,
                'payload' => $payload,
            ]);
        }

        if (! AggregateIdResolver::canResolveAggregateId($this->aggregateClassName, $aggregateIdentifiers)) {
            return $message;
        }

        return MessageBuilder::fromMessage($message)
            ->setHeader(AggregateMessage::AGGREGATE_ID, AggregateIdResolver::resolveArrayOfIdentifiers($this->aggregateClassName, $aggregateIdentifiers))
            ->build();
    }

    private function messageContainsCorrectAggregateId(Message $message): bool
    {
        if (! $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_ID)) {
            return false;
        }

        $aggregateIdentifiers = AggregateIdMetadata::createFrom($message->getHeaders()->get(AggregateMessage::AGGREGATE_ID))->getIdentifiers();

        if ($this->metadataIdentifierMapping !== []) {
            return array_keys($this->metadataIdentifierMapping) === array_keys($aggregateIdentifiers);
        }

        return array_keys($this->messageIdentifierMapping) === array_keys($aggregateIdentifiers);
    }
}
