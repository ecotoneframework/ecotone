<?php

declare(strict_types=1);

namespace Ecotone\Projecting\TaggedStream;

use function array_unique;
use function array_values;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\EventSourcing\Tagging\EventTagRegistry;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadConverter;

use function is_object;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class TagPartitionKeysConverter implements ParameterConverter
{
    public function __construct(
        private EventTagRegistry $eventTagRegistry,
        private string $tag,
        private PayloadConverter $eventConverter,
    ) {
    }

    /**
     * @return string[]
     */
    public function getArgumentFrom(Message $message): array
    {
        $event = $message->getPayload();
        if (! is_object($event) && $message->getHeaders()->containsKey(MessageHeaders::TYPE_ID)) {
            $event = $this->eventConverter->getArgumentFrom($message);
        }
        if (! is_object($event)) {
            return [];
        }

        $values = [];
        foreach ($this->eventTagRegistry->tagsFor($event) as $tag) {
            if ($tag['name'] === $this->tag) {
                $values[] = $tag['value'];
            }
        }

        return array_values(array_unique($values));
    }
}
