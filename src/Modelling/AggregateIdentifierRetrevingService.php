<?php

namespace Ecotone\Modelling;

use function array_key_exists;
use function array_keys;

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

use function is_array;
use function is_object;

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
    /**
     * @param array<string, array<string, string|null>> $perClassIdentifierMappings
     */
    public function __construct(
        private string $aggregateClassName,
        private ConversionService $conversionService,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private array $metadataIdentifierMapping,
        private array $identifierMapping,
        private ExpressionEvaluationService $expressionEvaluationService,
        private array $perClassIdentifierMappings,
    ) {

    }

    public function process(Message $message): Message
    {
        /** @TODO Ecotone 2.0 (remove) this. For backward compatibility because it's ran again when message is consumed from Queue e*/
        if ($this->messageContainsCorrectAggregateId($message)) {
            return $message;
        }

        $payloadClass = $this->resolvePayloadClassType($message);
        $payload = $this->deserializePayload($message, $payloadClass);

        $messageIdentifierMapping = $this->resolveMessageIdentifierMapping($payloadClass);

        if ($message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)) {
            $aggregateIds = $message->getHeaders()->get(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER);
            $aggregateIds = is_array($aggregateIds) ? $aggregateIds : [\array_key_first($messageIdentifierMapping) => $aggregateIds];

            return MessageBuilder::fromMessage($message)
                ->setHeader(AggregateMessage::AGGREGATE_ID, AggregateIdResolver::resolveArrayOfIdentifiers($this->aggregateClassName, $aggregateIds))
                ->removeHeader(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER)
                ->build();
        }

        $aggregateIdentifiers = [];
        foreach ($messageIdentifierMapping as $aggregateIdentifierName => $aggregateIdentifierMappingName) {
            if ($aggregateIdentifierMappingName === null) {
                $aggregateIdentifiers[$aggregateIdentifierName] = null;
                continue;
            }

            $sourcePayload = $payload;
            if (! is_object($payload) && $message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE)) {
                $sourcePayload = $message->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE);
            }

            $aggregateIdentifiers[$aggregateIdentifierName] =
                $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $sourcePayload)
                    ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdentifierMappingName), $sourcePayload)
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

    /**
     * Resolves the appropriate identifier mapping based on the payload type or TYPE_ID header.
     *
     * @return array<string, string|null>
     */
    private function resolveMessageIdentifierMapping(?string $payloadClass): array
    {
        if ($payloadClass !== null && isset($this->perClassIdentifierMappings[$payloadClass])) {
            return $this->perClassIdentifierMappings[$payloadClass];
        }

        // Fallback to empty-key mapping when payload class is unknown
        if (isset($this->perClassIdentifierMappings[''])) {
            return $this->perClassIdentifierMappings[''];
        }

        return [];
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

        $payloadClass = $this->resolvePayloadClassType($message);
        $messageIdentifierMapping = $this->resolveMessageIdentifierMapping($payloadClass);
        return array_keys($messageIdentifierMapping) === array_keys($aggregateIdentifiers);
    }

    public function resolvePayloadClassType(Message $message): mixed
    {
        $payload = $message->getPayload();
        if (is_object($payload)) {
            return get_class($payload);
        } elseif (
            $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)
            && Type::create($message->getHeaders()->get(MessageHeaders::TYPE_ID))->isClassNotInterface()
        ) {
            return $message->getHeaders()->get(MessageHeaders::TYPE_ID);
        } elseif (count($this->perClassIdentifierMappings) === 1) {
            $type = array_key_first($this->perClassIdentifierMappings);
            if ($type === '') {
                return null;
            }

            return $type;
        }

        return null;
    }

    public function deserializePayload(Message $message, ?string $payloadTargetClass): mixed
    {
        $payload = $message->getPayload();
        $mediaType = $message->getHeaders()->containsKey(MessageHeaders::CONTENT_TYPE)
            ? MediaType::parseMediaType($message->getHeaders()->get(MessageHeaders::CONTENT_TYPE))
            : MediaType::createApplicationXPHPWithTypeParameter(Type::createFromVariable($payload)->toString());

        if ($payloadTargetClass !== null && $this->conversionService->canConvert(
            Type::createFromVariable($payload),
            $mediaType,
            $targetType = Type::create($payloadTargetClass),
            MediaType::createApplicationXPHPWithTypeParameter($targetType)
        )) {
            $payload = $this->conversionService
                ->convert(
                    $payload,
                    Type::createFromVariable($payload),
                    $mediaType,
                    $targetType,
                    MediaType::createApplicationXPHPWithTypeParameter($targetType)
                );
        }

        return $payload;
    }
}
