<?php

declare(strict_types=1);

namespace Ecotone\Test;

use Throwable;

/**
 * licence Apache-2.0
 */
final class CoroutineTesting
{
    public static function runAtOnce(callable ...$coroutines): void
    {
        self::runAtOnceWithHooks(constant('SWOOLE_HOOK_ALL'), ...$coroutines);
    }

    public static function runAtOnceWithHooks(int $hooks, callable ...$coroutines): void
    {
        $coroutineClass = 'Swoole\\Coroutine';
        $runtimeClass = 'Swoole\\Runtime';
        $schedulerClass = 'Swoole\\Coroutine\\Scheduler';
        $failure = null;
        $runtimeClass::enableCoroutine($hooks);
        try {
            $scheduler = new $schedulerClass();
            $scheduler->add(static function () use ($coroutineClass, $coroutines, &$failure): void {
                foreach ($coroutines as $coroutine) {
                    $coroutineClass::create(static function () use ($coroutine, &$failure): void {
                        try {
                            $coroutine();
                        } catch (Throwable $exception) {
                            $failure ??= $exception;
                        }
                    });
                }
            });
            $scheduler->start();
        } finally {
            $runtimeClass::enableCoroutine(0);
        }

        if ($failure !== null) {
            throw $failure;
        }
    }
}
