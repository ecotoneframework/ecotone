<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Execution;

use Ecotone\Test\CoroutineTesting;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Swoole\Coroutine;
use Swoole\Runtime;

/**
 * licence Apache-2.0
 * @internal
 */
#[RequiresMethod(Coroutine::class, 'create')]
final class CoroutineTestingTest extends TestCase
{
    public function test_callables_run_at_once_each_on_a_coroutine_of_its_own(): void
    {
        $finished = [];

        CoroutineTesting::runAtOnce(
            static function () use (&$finished): void {
                usleep(20_000);
                $finished[] = 'slow';
            },
            static function () use (&$finished): void {
                $finished[] = 'quick';
            },
        );

        $this->assertSame(['quick', 'slow'], $finished);
    }

    public function test_first_failure_of_a_callable_is_thrown_after_every_callable_finished(): void
    {
        $finished = [];
        $failure = null;

        try {
            CoroutineTesting::runAtOnce(
                static fn () => throw new RuntimeException('first'),
                static function () use (&$finished): void {
                    usleep(1_000);
                    $finished[] = 'other';
                },
                static fn () => throw new RuntimeException('second'),
            );
        } catch (RuntimeException $failure) {
        }

        $this->assertSame('first', $failure?->getMessage());
        $this->assertSame(['other'], $finished);
    }

    public function test_hooks_are_switched_off_after_the_callables_also_when_one_failed(): void
    {
        try {
            CoroutineTesting::runAtOnce(static fn () => throw new RuntimeException('failed'));
        } catch (RuntimeException) {
        }

        $this->assertSame(0, Runtime::getHookFlags());
    }
}
