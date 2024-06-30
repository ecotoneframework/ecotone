<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class ServiceExpectingTwoArguments
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingTwoArguments implements DefinedObject
{
    private $wasCalled = false;

    public static function create(): self
    {
        return new self();
    }

    public function withReturnValue(string $name, string $surname): string
    {
        $this->wasCalled = true;
        return $name . $surname;
    }

    public function withoutReturnValue(string $name, string $surname): void
    {
        $this->wasCalled = true;
    }

    public function payloadAndHeaders($payload, array $headers)
    {
        return [
            'payload' => $payload,
            'message_id' => $headers[MessageHeaders::MESSAGE_ID],
        ];
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
        return new Definition(self::class, [], 'create');
    }
}
