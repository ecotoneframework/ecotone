<?php


namespace Ecotone\Messaging\Config;


use Ecotone\Messaging\MessageChannel;

class StubConfiguredMessagingSystem implements ConfiguredMessagingSystem
{
    public function getGatewayByName(string $gatewayReferenceName): object
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getNonProxyGatewayByName(string $gatewayReferenceName): \Ecotone\Messaging\Config\NonProxyCombinedGateway
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getGatewayList(): iterable
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function run(string $endpointId): void
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function list(): array
    {
        throw new \InvalidArgumentException("Calling stub messaging system");
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return null;
    }
}