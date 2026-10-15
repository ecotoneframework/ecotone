<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\Messaging\Attribute\EndpointAnnotation;

/**
 * licence Apache-2.0
 */
final class MissingHandlerMessage
{
    /**
     * @param class-string<EndpointAnnotation> $handlerAttribute
     */
    public static function forFlowTesting(string $messageKind, string $routingKey, string $handlerAttribute): string
    {
        $handler = (new UnregisteredHandlerFinder())->find($routingKey, $handlerAttribute);
        if ($handler !== null) {
            [$handlerClass, $handlerMethod] = $handler;
            $handlerNamespace = substr($handlerClass, 0, (int) strrpos($handlerClass, '\\'));

            return "Can't send {$messageKind} to {$routingKey}. It is handled by {$handlerClass}::{$handlerMethod}(), which is not registered in this Ecotone Lite bootstrap. Add {$handlerClass} to the classesToResolve of EcotoneLite::bootstrapFlowTesting(), or load its namespace with ServiceConfiguration::withNamespaces(['{$handlerNamespace}']).";
        }

        $attributeShortName = substr($handlerAttribute, (int) strrpos($handlerAttribute, '\\') + 1);
        $handlerDeclaration = class_exists($routingKey)
            ? "#[{$attributeShortName}] to a method taking {$routingKey}"
            : "#[{$attributeShortName}('{$routingKey}')] to a method";
        $kindName = ucfirst($messageKind);

        return "Can't send {$messageKind} to {$routingKey}. No {$kindName} Handler is registered for it in this Ecotone Lite bootstrap. Add {$handlerDeclaration} and add its class to the classesToResolve of EcotoneLite::bootstrapFlowTesting().";
    }
}
