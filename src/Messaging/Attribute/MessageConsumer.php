<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * Base attribute for any Inbound Channel Adapter consuming from an external system
 * (Kafka, AMQP, scheduled tasks, etc.). Subclasses include #[KafkaConsumer], #[RabbitConsumer],
 * and #[ChannelAdapter] (the base for #[Scheduled]).
 *
 * licence Apache-2.0
 */
class MessageConsumer extends IdentifiedAnnotation
{
}
