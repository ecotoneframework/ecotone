<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\DatabaseTransaction;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\WithoutDatabaseTransaction;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Modelling\Config\MessageBusChannel;

use function in_array;
use function is_a;
use function is_object;

/**
 * licence Apache-2.0
 */
final class CommandsWithoutDatabaseTransaction implements DefinedObject
{
    /**
     * @param string[] $routingKeys
     * @param string[] $commandClasses
     */
    public function __construct(private array $routingKeys, private array $commandClasses)
    {
    }

    public static function find(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        $routingKeys = [];
        $commandClasses = [];
        foreach ($annotationFinder->findAnnotatedMethods(WithoutDatabaseTransaction::class) as $annotatedMethod) {
            if (! $annotatedMethod->hasMethodAnnotation(CommandHandler::class)) {
                continue;
            }

            foreach ($annotatedMethod->getMethodAnnotationsWithType(CommandHandler::class) as $commandHandler) {
                if ($commandHandler->getInputChannelName() !== '') {
                    $routingKeys[] = $commandHandler->getInputChannelName();
                    continue;
                }

                $interfaceToCall = $interfaceToCallRegistry->getFor($annotatedMethod->getClassName(), $annotatedMethod->getMethodName());
                if ($interfaceToCall->hasNoParameters()) {
                    continue;
                }

                foreach ($interfaceToCall->getFirstParameter()->getTypeDescriptor()->getUnionTypes() as $unionType) {
                    $commandClasses[] = (string) $unionType;
                }
            }
        }

        return new self($routingKeys, $commandClasses);
    }

    public function isRoutedToHandlerWithoutTransaction(Message $message): bool
    {
        if ($message->getHeaders()->containsKey(MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME)) {
            return in_array($message->getHeaders()->get(MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME), $this->routingKeys, true);
        }

        $payload = $message->getPayload();
        if (! is_object($payload)) {
            return false;
        }

        foreach ($this->commandClasses as $commandClass) {
            if (is_a($payload, $commandClass)) {
                return true;
            }
        }

        return false;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->routingKeys, $this->commandClasses]);
    }
}
