<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\MessageProcessor;

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
 *
 */
final class UngatheredSplitterProcessor implements MessageProcessor
{
    public function __construct(private MessageProcessor $splitter, private string $endpointId)
    {
    }

    public function process(Message $message): ?Message
    {
        if ($message->getHeaders()->containsKey(GatheringHeaders::GROUP_ID)) {
            throw self::refusal($this->endpointId);
        }

        return $this->splitter->process($message);
    }

    public static function refusal(string $endpointId): ConfigurationException
    {
        return ConfigurationException::create("Splitter '{$endpointId}' has no gatherChannelName inside a gathered flow, so its sub-parts cannot be accounted for as one outer part. Set gatherChannelName on this #[Splitter], or send the sub-work through CommandBus::send() or EventBus::publish() to leave the gathered flow.");
    }
}
