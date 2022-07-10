<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class ServiceExpectingOneArgument
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingOneArgument
{
    private $wasCalled = false;

    public static function create() : self
    {
        return new self();
    }

    public function withReturnValue(string $name) : string
    {
        $this->wasCalled = true;
        return $name;
    }

    public function withoutReturnValue(string $name) : void
    {
        $this->wasCalled = true;
    }

    public function withNullReturnValue(string $name) : ?string
    {
        return null;
    }

    public function withArrayReturnValue(string $name) : array
    {
        return [$name];
    }

    public function withArrayTypeHintAndArrayReturnValue(array $values) : array
    {
        return $values;
    }

    /**
     * @param $value
     * @return ServiceExpectingOneArgument[]|\stdClass[]
     */
    public function withCollectionAndArrayReturnType($value) : array
    {
        return $value;
    }

    public function withUnionReturnType($value) : ServiceExpectingOneArgument|\stdClass
    {
        return $value;
    }

    public function withUnionParameter(\stdClass|string $value)
    {
        return $value;
    }

    public function withUnionParameterWithUuid(\stdClass|UuidInterface $value)
    {
        return $value;
    }

    public function withUnionParameterWithArray(\stdClass|array $value)
    {
        return $value;
    }

    public function withInterface(UuidInterface $value)
    {
        return $value;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}