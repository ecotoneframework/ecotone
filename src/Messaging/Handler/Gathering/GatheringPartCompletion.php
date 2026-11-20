<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Gathering;

use Ecotone\Api\Messaging\ErrorMessage;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Support\MessageBuilder;

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
final class GatheringPartCompletion implements PartCompletion
{
    public function __construct(private ChannelResolver $channelResolver, private MessageBuilder $messageBuilder)
    {
    }

    public function validateRoute(Message $request, array $destinations): void
    {
        if ($request->getHeaders()->containsKey(GatheringHeaders::GROUP_ID) && count($destinations) > 1) {
            throw self::multipleDestinations($destinations);
        }
    }

    public static function multipleDestinations(array $destinations): ConfigurationException
    {
        $channels = "'" . implode("', '", $destinations) . "'";

        return ConfigurationException::create("Router resolved multiple destinations {$channels} inside a gathered flow, so its branches cannot be accounted for as one part. Return one destination, replace the router with a #[Splitter] naming its own gatherChannelName, or send the branch work through CommandBus::send() or EventBus::publish() to leave the gathered flow.");
    }

    public function complete(Message $request, ?Message $reply): bool
    {
        if (! $request->getHeaders()->containsKey(GatheringHeaders::GROUP_ID) || ErrorMessage::isErrorMessage($request)) {
            return false;
        }
        $report = $this->messageBuilder->fromMessage($reply ?? $request);
        foreach ([GatheringHeaders::GROUP_ID, GatheringHeaders::PART_NUMBER, GatheringHeaders::SIZE, GatheringHeaders::CHANNEL_NAME] as $header) {
            $report = $report->setHeader($header, $request->getHeaders()->get($header));
        }
        $report = $report->setHeader(GatheringHeaders::NO_RESULT, $reply === null);
        $this->channelResolver->resolve($request->getHeaders()->get(GatheringHeaders::CHANNEL_NAME))->send($report->build());

        return true;
    }
}
