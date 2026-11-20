<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Execution;

use Closure;
use Fiber;
use WeakMap;

/**
 * licence Apache-2.0
 */
final class ExecutionScope
{
    private static ?WeakMap $statesOfProcess = null;
    private static ?WeakMap $statesOfFibers = null;

    /**
     * @template T of object
     * @param Closure(): T $initialState
     * @return T
     */
    public static function stateOf(object $owner, Closure $initialState): object
    {
        $states = self::statesOfCurrentExecution();

        return $states[$owner] ??= $initialState();
    }

    /**
     * @template T of object
     * @param class-string<T> $stateClass
     * @return T|null
     */
    public static function existingStateOf(object $owner, string $stateClass): ?object
    {
        $state = self::statesOfCurrentExecution()[$owner] ?? null;

        return $state instanceof $stateClass ? $state : null;
    }

    public static function current(): object
    {
        return self::statesOfCurrentExecution();
    }

    public static function isConcurrent(): bool
    {
        return self::coroutineContext() !== null || Fiber::getCurrent() !== null;
    }

    public static function coroutineChannelClass(): ?string
    {
        if (class_exists(\OpenSwoole\Coroutine::class, false) && \OpenSwoole\Coroutine::getCid() > 0) {
            return 'OpenSwoole\\Coroutine\\Channel';
        }
        if (class_exists(\Swoole\Coroutine::class, false) && \Swoole\Coroutine::getCid() > 0) {
            return 'Swoole\\Coroutine\\Channel';
        }

        return null;
    }

    public static function isCoroutineStoppedByPostgresQueries(): bool
    {
        $coroutineClass = self::coroutineClass();
        if ($coroutineClass === null || $coroutineClass::getCid() < 1) {
            return false;
        }
        if ($coroutineClass === 'OpenSwoole\\Coroutine' || ! defined('SWOOLE_HOOK_PDO_PGSQL')) {
            return true;
        }

        return (('Swoole\\Runtime')::getHookFlags() & constant('SWOOLE_HOOK_PDO_PGSQL')) === 0;
    }

    public static function lastStartedCoroutineId(): int
    {
        $coroutineClass = self::coroutineClass();

        return $coroutineClass === null ? 0 : (int) ($coroutineClass::stats()['coroutine_last_cid'] ?? 0);
    }

    private static function statesOfCurrentExecution(): WeakMap
    {
        $coroutineClass = self::coroutineClass();
        if ($coroutineClass !== null && $coroutineClass::getCid() > 0) {
            return self::statesOfCoroutine($coroutineClass, $coroutineClass::getCid());
        }

        $fiber = Fiber::getCurrent();
        if ($fiber !== null) {
            self::$statesOfFibers ??= new WeakMap();

            return self::$statesOfFibers[$fiber] ??= new WeakMap();
        }

        return self::$statesOfProcess ??= new WeakMap();
    }

    /**
     * @param class-string $coroutineClass
     */
    private static function statesOfCoroutine(string $coroutineClass, int $coroutineId): WeakMap
    {
        $context = $coroutineClass::getContext($coroutineId);
        if (isset($context[self::class])) {
            return $context[self::class];
        }

        $states = new WeakMap();
        $context[self::class] = $states;
        foreach (self::statesOfExecutionThatStarted($coroutineClass, $coroutineId) as $owner => $state) {
            $copy = $state instanceof CopiedToChildExecution ? $state->copyForChildExecution($coroutineId) : null;
            if ($copy !== null) {
                $states[$owner] = $copy;
            }
        }

        return $states;
    }

    /**
     * @param class-string $coroutineClass
     */
    private static function statesOfExecutionThatStarted(string $coroutineClass, int $coroutineId): WeakMap
    {
        $parentCoroutineId = (int) $coroutineClass::getPcid($coroutineId);
        if ($parentCoroutineId > 0 && $coroutineClass::getContext($parentCoroutineId) !== null) {
            return self::statesOfCoroutine($coroutineClass, $parentCoroutineId);
        }
        if ($parentCoroutineId > 0) {
            return new WeakMap();
        }

        return self::$statesOfProcess ??= new WeakMap();
    }

    private static function coroutineContext(): ?object
    {
        $coroutineClass = self::coroutineClass();

        return $coroutineClass !== null && $coroutineClass::getCid() > 0 ? $coroutineClass::getContext() : null;
    }

    /**
     * @return class-string|null
     */
    private static function coroutineClass(): ?string
    {
        if (class_exists(\OpenSwoole\Coroutine::class, false)) {
            return \OpenSwoole\Coroutine::class;
        }
        if (class_exists(\Swoole\Coroutine::class, false)) {
            return \Swoole\Coroutine::class;
        }

        return null;
    }
}
