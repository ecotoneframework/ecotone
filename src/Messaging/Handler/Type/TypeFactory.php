<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

use Ecotone\Messaging\Handler\Type;

trait TypeFactory
{
    public static function boolean(): Type
    {
        return new BuiltinType(TypeIdentifier::BOOL);
    }

    /**
     * Should be used instead of array, if array is not composed of scalar types or composition of types is unknown
     */
    public static function iterable(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::ITERABLE);
    }

    public static function string(): Type
    {
        return new BuiltinType(TypeIdentifier::STRING);
    }

    public static function int(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::INTEGER);
    }

    public static function null(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::NULL);
    }

    public static function float(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::FLOAT);
    }

    public static function false(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::FALSE);
    }

    public static function true(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::TRUE);
    }

    public static function array(?Type $firstType = null, ?Type $secondType = null): BuiltinType|GenericType
    {
        if ($firstType) {
            $types = [$firstType];
            if ($secondType) {
                $types[] = $secondType;
            }
            return new GenericType(new BuiltinType(TypeIdentifier::ARRAY), ...$types);
        } else {
            return new BuiltinType(TypeIdentifier::ARRAY);
        }
    }

    public static function arrayShape(array $array): ArrayShapeType
    {
        return new ArrayShapeType($array);
    }

    public static function object(?string $className = null, Type ...$genericTypes): BuiltinType|ObjectType|GenericType
    {
        if ($className) {
            if (! empty($genericTypes)) {
                return new GenericType(new ObjectType($className), ...$genericTypes);
            } else {
                return new ObjectType($className);
            }
        }

        return new BuiltinType(TypeIdentifier::OBJECT);
    }

    public static function attribute(string $className): BuiltinType|ObjectType|GenericType
    {
        return new ObjectType($className, true);
    }

    public static function void(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::VOID);
    }

    public static function anything(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::ANYTHING);
    }

    public static function resource(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::RESOURCE);
    }

    public static function callable(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::CALLABLE);
    }

    public static function never(): BuiltinType
    {
        return new BuiltinType(TypeIdentifier::NEVER);
    }

    public static function union(Type ...$types): Type
    {
        return UnionType::createWith($types);
    }
}
