<?php

namespace Ecotone\Messaging\Config\Container;

use Ecotone\Messaging\Support\InvalidArgumentException;
use Exception;

use function get_class;
use function is_object;
use function serialize;

/**
 * This is a helper class to build a definition from an instance.
 * It should not be used in production code, as it defeats opcache optimizations.
 * It is used in ecotone during the transition to fully compilable components
 *
 * @internal
 */
/**
 * licence Apache-2.0
 */
class DefinitionHelper
{
    public static function buildDefinitionFromInstance(object $object): Definition
    {
        return new Definition(get_class($object), [serialize($object)], [self::class, 'unserializeSerializedObject']);
    }

    public static function buildAttributeDefinitionFromInstance(object $object, ?AttributeDeclaration $declaration = null): AttributeDefinition
    {
        if ($object instanceof DefinedObject) {
            $definition = $object->getDefinition();

            return new AttributeDefinition(
                $definition->getClassName(),
                $definition->getArguments(),
                $definition->hasFactory() ? $definition->getFactory() : '',
                $declaration,
            );
        }

        try {
            return new AttributeDefinition(get_class($object), [serialize($object)], [self::class, 'unserializeSerializedObject'], $declaration);
        } catch (Exception $exception) {
            if ($declaration !== null) {
                return $declaration->toAttributeDefinition();
            }

            throw self::nonSerializableAttributeException(get_class($object), $exception);
        }
    }

    public static function unserializeSerializedObject(string $serializedObject): object
    {
        return unserialize($serializedObject);
    }

    public static function resolvePotentialComplexAttribute(AttributeDefinition $attributeDefinition): Definition
    {
        $attributeArguments = $attributeDefinition->getArguments();
        if (self::isComplexArgument($attributeArguments)) {
            $instance = $attributeDefinition->instance();
            if ($instance instanceof DefinedObject) {
                return $instance->getDefinition();
            }

            try {
                return DefinitionHelper::buildDefinitionFromInstance($instance);
            } catch (Exception $exception) {
                $declaration = $attributeDefinition->getDeclaration();
                if ($declaration !== null) {
                    return $declaration->toAttributeDefinition();
                }

                throw self::nonSerializableAttributeException($attributeDefinition->getClassName(), $exception);
            }
        } else {
            return $attributeDefinition;
        }
    }

    private static function nonSerializableAttributeException(string $attributeClassName, Exception $exception): Exception
    {
        return InvalidArgumentException::create("Attribute {$attributeClassName} contains value that cannot be serialized (e.g. Closure). Closures inside attributes are supported only when declared directly on a class, method or parameter. Original error: {$exception->getMessage()}");
    }

    private static function isComplexArgument(mixed $attributeArguments): bool
    {
        if (is_array($attributeArguments)) {
            foreach ($attributeArguments as $argument) {
                if (self::isComplexArgument($argument)) {
                    return true;
                }
            }
        } elseif (is_object($attributeArguments)) {
            return true;
        }
        return false;
    }
}
