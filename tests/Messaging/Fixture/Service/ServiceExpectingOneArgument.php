<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Message;
use Ramsey\Uuid\UuidInterface;
use stdClass;

/**
 * Class ServiceExpectingOneArgument
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingOneArgument implements DefinedObject
{
    private $wasCalled = false;

    public static function create(): self
    {
        return new self();
    }

    public function withReturnValue(string $name): string
    {
        $this->wasCalled = true;
        return $name . '_called';
    }

    public function withReturnMixed(mixed $value): mixed
    {
        return $value;
    }

    #[ServiceActivator('withoutReturnValue')]
    public function withoutReturnValue(string $name): void
    {
        $this->wasCalled = true;
    }

    public function withNullReturnValue(string $name): ?string
    {
        return null;
    }

    public function withArrayReturnValue(string $name): array
    {
        return ['some' => $name];
    }

    /**
     * @param stdClass[] $value
     * @return stdClass[]
     */
    public function withArrayStdClasses(array $value): array
    {
        return $value;
    }

    public function withArrayTypeHintAndArrayReturnValue(array $values): array
    {
        return $values;
    }

    /**
     * @param $value
     * @return ServiceExpectingOneArgument[]|stdClass[]
     */
    public function withCollectionAndArrayReturnType($value): array
    {
        return $value;
    }

    public function withUnionReturnType($value): ServiceExpectingOneArgument|stdClass
    {
        return $value;
    }

    public function withUnionParameter(stdClass|string $value)
    {
        return $value;
    }

    public function withUnionParameterWithUuid(stdClass|UuidInterface $value)
    {
        return $value;
    }

    public function withUnionParameterWithArray(stdClass|array $value)
    {
        return $value;
    }

    public function withInterface(UuidInterface $value)
    {
        return $value;
    }

    public function withMessage(Message $message): Message
    {
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
