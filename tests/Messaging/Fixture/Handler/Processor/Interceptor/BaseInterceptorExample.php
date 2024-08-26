<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Class BaseInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
abstract class BaseInterceptorExample implements DefinedObject
{
    /**
     * @var bool
     */
    protected $wasCalled = false;
    /**
     * @var mixed
     */
    protected $valueToReturn;
    /**
     * @var array
     */
    protected $argumentsToReplace;


    /**
     * StubCallSavingService constructor.
     * @param $valueToReturn
     */
    private function __construct($valueToReturn)
    {
        $this->valueToReturn = $valueToReturn;
    }

    public static function create(): self
    {
        return new static(null);
    }

    /**
     * @param array $toReplace
     * @return static
     */
    public static function createWithArgumentsToReplace(array $toReplace): self
    {
        $self = self::create();
        $self->argumentsToReplace = $toReplace;

        return $self;
    }

    /**
     * @param $valueToReturn
     * @return static
     */
    public static function createWithReturnType($valueToReturn): self
    {
        return new static($valueToReturn);
    }

    /**
     * @return bool
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }

    protected function markAsCalled(): void
    {
        $this->wasCalled = true;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->valueToReturn]);
    }
}
