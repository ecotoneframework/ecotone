<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use DateTimeInterface;
use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\Endpoint\TimeToLive;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TimeSpan;

/**
 * Class EndpointHeadersInterceptor
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EndpointHeadersInterceptor implements DefinedObject
{
    public function __construct(private ExpressionEvaluationService $expressionEvaluationService)
    {

    }

    public function addMetadata(Message $message, ?AddHeader $addHeader, ?Delayed $delayed, ?Priority $priority, ?TimeToLive $timeToLive, ?RemoveHeader $removeHeader): array
    {
        $metadata = [];

        if ($addHeader) {
            $metadata[$addHeader->getHeaderName()] = $addHeader->getHeaderValue();

            if ($addHeader->getExpression()) {
                $metadata[$addHeader->getHeaderName()] = $this->expressionEvaluationService->evaluate($addHeader->getExpression(), [
                    'payload' => $message->getPayload(),
                    'headers' => $message->getHeaders()->headers(),
                ]);
            }
        }

        if ($delayed) {
            $metadata[MessageHeaders::DELIVERY_DELAY] = $delayed->getHeaderValue();

            if ($delayed->getExpression()) {
                $metadata[MessageHeaders::DELIVERY_DELAY] = $this->expressionEvaluationService->evaluate($delayed->getExpression(), [
                    'payload' => $message->getPayload(),
                    'headers' => $message->getHeaders()->headers(),
                ]);
            }

            $type = Type::createFromVariable($metadata[MessageHeaders::DELIVERY_DELAY]);
            if (! $type->isCompatibleWith(UnionType::createWith([
                Type::int(),
                Type::object(TimeSpan::class),
                Type::object(DateTimeInterface::class),
            ]))) {
                throw ConfigurationException::create("Delivery delay should be either integer, TimeSpan or DateTimeInterface, but got {$type->toString()}");
            }
        }

        if ($priority) {
            $metadata[MessageHeaders::PRIORITY] = $priority->getHeaderValue();

            if ($priority->getExpression()) {
                $metadata[MessageHeaders::PRIORITY] = $this->expressionEvaluationService->evaluate($priority->getExpression(), [
                    'payload' => $message->getPayload(),
                    'headers' => $message->getHeaders()->headers(),
                ]);
            }
        }

        if ($timeToLive) {
            $metadata[MessageHeaders::TIME_TO_LIVE] = $timeToLive->getHeaderValue();

            if ($timeToLive->getExpression()) {
                $metadata[MessageHeaders::TIME_TO_LIVE] = $this->expressionEvaluationService->evaluate($timeToLive->getExpression(), [
                    'payload' => $message->getPayload(),
                    'headers' => $message->getHeaders()->headers(),
                ]);
            }

            $type = Type::createFromVariable($metadata[MessageHeaders::TIME_TO_LIVE]);
            if (! $type->isCompatibleWith(UnionType::createWith([
                Type::int(),
                Type::object(TimeSpan::class),
            ]))) {
                throw ConfigurationException::create("Delivery delay should be either integer or TimeSpan, but got {$type->toString()}");
            }
        }

        if ($removeHeader) {
            $metadata[$removeHeader->getHeaderName()] = null;
        }

        return $metadata;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            Reference::to(ExpressionEvaluationService::REFERENCE),
        ]);
    }
}
