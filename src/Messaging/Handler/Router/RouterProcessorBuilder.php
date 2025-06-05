<?php

namespace Ecotone\Messaging\Handler\Router;

use Ecotone\Messaging\Config\Container\ChannelReference;
use Ecotone\Messaging\Config\Container\CompilableBuilder;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\Processor\SendToChannelProcessor;

/**
 * licence Apache-2.0
 */
class RouterProcessorBuilder implements CompilableBuilder
{
    public function __construct(
        private Definition $routeSelectorDefinition,
        private array $routeMap = [],
        private bool $singleRoute = true,
    ) {
    }

    public static function createHeaderMappingRouter(string $headerName, array $headerValueToChannelMapping): self
    {
        return new self(HeaderMappingRouter::create($headerName, $headerValueToChannelMapping)->getDefinition());
    }

    public static function createRecipientListRouter(array $recipientList): self
    {
        $sendToChannelMap = [];
        foreach ($recipientList as $channel) {
            $sendToChannelMap[$channel] = new Definition(SendToChannelProcessor::class, [
                new ChannelReference($channel),
            ]);
        }
        return new self(
            (new RecipientListRouter($recipientList))->getDefinition(),
            $sendToChannelMap,
            false
        );
    }

    public static function createHeaderExistsRouter(string $headerName, CompilableBuilder $existsProcessor, CompilableBuilder $fallbackProcessor): self
    {
        return new self(
            HeaderExistsRouter::create($headerName, 'exists', 'fallback')->getDefinition(),
            [
                'exists' => $existsProcessor,
                'fallback' => $fallbackProcessor,
            ]
        );
    }

    public function route(string $routeName, CompilableBuilder $processor): self
    {
        $this->routeMap[$routeName] = $processor;

        return $this;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        $routeMap = [];
        foreach ($this->routeMap as $routeName => $processor) {
            $routeMap[$routeName] = $processor->compile($builder);
        }
        $routeResolver = new Definition(InMemoryRouteResolver::class, [
            $routeMap,
        ]);
        return new Definition(RouterProcessor::class, [
            $this->routeSelectorDefinition,
            $routeResolver,
            $this->singleRoute,
        ]);
    }
}
