<?php

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Class ServiceWithReturnValue
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceWithReturnValue implements CallableService, DefinedObject
{
    /**
     * @var bool
     */
    private $wasCalled = false;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        $this->wasCalled = true;
        return 'johny';
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
        return new Definition(self::class, factory: 'create');
    }
}
