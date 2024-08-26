<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;

/**
 * Class HeaderValueRouter
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 * @internal
 */
/**
 * licence Apache-2.0
 */
final class HeaderMappingRouter implements RouteSelector, DefinedObject
{
    private string $headerName;
    private array $headerValueToChannelMapping;

    /**
     * PayloadTypeRouter constructor.
     * @param string $headerName
     * @param array $typeToChannelMapping
     */
    private function __construct(string $headerName, array $typeToChannelMapping)
    {
        $this->headerName = $headerName;
        $this->headerValueToChannelMapping = $typeToChannelMapping;
    }

    /**
     * @param string $headerName
     * @param array $typeToChannelMapping
     *
     * @return HeaderMappingRouter
     */
    public static function create(string $headerName, array $typeToChannelMapping): self
    {
        return new self($headerName, $typeToChannelMapping);
    }

    /**
     * @param Message $message
     * @return array
     */
    public function route(Message $message): array
    {
        $header = $message->getHeaders()->get($this->headerName);

        $channelsToRoute = [];
        foreach ($this->headerValueToChannelMapping as $type => $channelName) {
            if ($header == $type) {
                $channelsToRoute[] = $channelName;
            }
        }

        return $channelsToRoute;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [
            $this->headerName,
            $this->headerValueToChannelMapping,
        ], 'create');
    }
}
