<?php
declare(strict_types=1);

namespace Fixture\Service;

/**
 * Class CalculatingService
 * @package Fixture\Service
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CalculatingService
{
    /**
     * @var int
     */
    private $secondValueForMathOperations;

    /**
     * @param int $secondValueForMathOperations
     * @return CalculatingService
     */
    public static function create(int $secondValueForMathOperations) : self
    {
        $calculatingService = new self();
        $calculatingService->secondValueForMathOperations = $secondValueForMathOperations;

        return $calculatingService;
    }

    public function sum(int $amount) : int
    {
        return $amount + $this->secondValueForMathOperations;
    }

    public function subtract(int $amount) : int
    {
        return $amount - $this->secondValueForMathOperations;
    }

    public function multiply(int $amount) : int
    {
        return $amount * $this->secondValueForMathOperations;
    }
}