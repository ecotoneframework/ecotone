<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Type;

enum TypeIdentifier: string
{
    //    scalar types
    case INTEGER = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case FALSE = 'false';
    case TRUE = 'true';
    case STRING = 'string';

    //    compound types
    case ARRAY = 'array';
    case ITERABLE = 'iterable';
    case CALLABLE = 'callable';
    case OBJECT = 'object';

    //    resource
    case RESOURCE = 'resource';

    case ANYTHING = 'mixed';
    case VOID = 'void';

    case NULL = 'null';

    case NEVER = 'never';

    public function isScalar(): bool
    {
        return in_array($this, self::scalars(), true);
    }

    /**
     * @return TypeIdentifier[]
     */
    public static function scalars(): array
    {
        return [self::INTEGER, self::FLOAT, self::BOOL, self::STRING, self::FALSE, self::TRUE];
    }

    public function isBoolean(): bool
    {
        return in_array($this, [self::BOOL, self::FALSE, self::TRUE], true);
    }
}
