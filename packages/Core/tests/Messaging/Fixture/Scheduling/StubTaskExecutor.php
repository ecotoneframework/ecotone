<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Scheduling;

use Ecotone\Messaging\Scheduling\TaskExecutor;

/**
 * Class StubTaskExecutor
 * @package Test\Ecotone\Messaging\Fixture\Scheduling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class StubTaskExecutor implements TaskExecutor
{
    /**
     * @var bool
     */
    private $wasCalled = false;
    /**
     * @var int
     */
    private $calledTimes = 0;

    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }

    public function execute(): void
    {
        $this->wasCalled = true;
        $this->calledTimes++;
    }

    /**
     * @return bool
     */
    public function wasCalled() : bool
    {
        return $this->wasCalled;
    }

    /**
     * @return int
     */
    public function getCalledTimes(): int
    {
        return $this->calledTimes;
    }
}