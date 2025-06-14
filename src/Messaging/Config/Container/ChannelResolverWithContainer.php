<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\MessageChannel;
use Psr\Container\ContainerInterface;

/**
 * licence Apache-2.0
 */
class ChannelResolverWithContainer implements ChannelResolver
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function resolve(string|MessageChannel $channelName): MessageChannel
    {
        if ($channelName instanceof MessageChannel) {
            return $channelName;
        }

        if (! $this->hasChannelWithName($channelName)) {
            throw MessageChannelNotFound::create(sprintf('Channel with name "%s" was not found. Have you used correct name and annotated Message Handler correctly?', $channelName));
        }

        return $this->container->get(new ChannelReference($channelName));
    }

    public function hasChannelWithName(string $channelName): bool
    {
        $id = (string) new ChannelReference($channelName);
        return $this->container->has($id);
    }
}
