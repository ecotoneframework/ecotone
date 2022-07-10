<?php

namespace Ecotone\Messaging\Handler;


use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class TypeDescriptor
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Type
{
    /**
     * @param Type $toCompare
     * @return bool
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \ReflectionException
     */
    public function isCompatibleWith(Type $toCompare): bool;

    /**
     * @param Type $toCompare
     * @return bool
     */
    public function equals(Type $toCompare): bool;

    /**
     * @param string $interfaceName
     * @return bool
     */
    public function isClassOfType(string $interfaceName): bool;

    /**
     * @return bool
     */
    public function isCompoundType() : bool;

    /**
     * @return bool
     */
    public function isResource() : bool;

    /**
     * @return bool
     */
    public function isIterable(): bool;

    /**
     * @return TypeDescriptor[]
     */
    public function getUnionTypes(): array;

    public function isMessage(): bool;

    /**
     * @return bool
     */
    public function isCollection(): bool;

    /**
     * @return bool
     */
    public function isNonCollectionArray(): bool;

    /**
     * @return bool
     */
    public function isBoolean(): bool;

    /**
     * @return bool
     */
    public function isVoid(): bool;

    /**
     * @return bool
     */
    public function isString(): bool;

    /**
     * @return bool
     */
    public function isClassOrInterface(): bool;

    public function isClassNotInterface() : bool;

    /**
     * @return bool
     */
    public function isCompoundObjectType(): bool;

    /**
     * @return bool
     */
    public function isPrimitive(): bool;

    /**
     * @return bool
     */
    public function isAnything(): bool;

    public function isInterface(): bool;

    public function isAbstractClass(): bool;

    /**
     * @return bool
     */
    public function isScalar(): bool;

    /**
     * @return bool
     */
    public function isUnionType() : bool;

    /**
     * @return string
     */
    public function toString(): string;
}