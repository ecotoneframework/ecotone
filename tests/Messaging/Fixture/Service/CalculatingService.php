<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Service;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * Class CalculatingService
 * @package Test\Ecotone\Messaging\Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CalculatingService implements DefinedObject
{
    /**
     * @var int
     */
    private $secondValueForMathOperations;

    private $lastResult;

    /**
     * @param int $secondValueForMathOperations
     * @return CalculatingService
     */
    public static function create(int $secondValueForMathOperations): self
    {
        $calculatingService = new self();
        $calculatingService->secondValueForMathOperations = $secondValueForMathOperations;

        return $calculatingService;
    }

    public function result(int $amount): int
    {
        $this->lastResult = $amount;
        return $amount;
    }

    public function sum(int $amount): int
    {
        return $amount + $this->secondValueForMathOperations;
    }

    public function subtract(int $amount): int
    {
        return $amount - $this->secondValueForMathOperations;
    }

    public function multiply(int $amount): int
    {
        return $amount * $this->secondValueForMathOperations;
    }

    /**
     * @return mixed
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->secondValueForMathOperations], 'create');
    }
}
