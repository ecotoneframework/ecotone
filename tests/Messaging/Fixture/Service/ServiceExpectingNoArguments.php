<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

/**
 * Class ServiceExpectingOneArgument
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ServiceExpectingNoArguments
{
    private $wasCalled = false;

    private $returnValue;

    /**
     * ServiceExpectingNoArguments constructor.
     * @param $returnValue
     */
    private function __construct($returnValue)
    {
        $this->returnValue = $returnValue;
    }

    public static function createWithReturnValue(string $returnValue) : self
    {
        return new self($returnValue);
    }

    public static function create() : self
    {
        return new self("test");
    }

    public function withReturnValue() : string
    {
        $this->wasCalled = true;

        return $this->returnValue;
    }

    public function withoutReturnValue() : void
    {
        $this->wasCalled = true;
    }

    public function withNullReturnValue() : ?string
    {
        return null;
    }

    public function withArrayReturnValue() : array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function wasCalled(): bool
    {
        return $this->wasCalled;
    }
}