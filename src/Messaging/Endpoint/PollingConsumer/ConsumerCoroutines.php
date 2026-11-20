<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\PollingConsumer;

use ArrayObject;
use Closure;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerLifecycle;
use Throwable;

/**
 * licence Apache-2.0
 */
final class ConsumerCoroutines
{
    /**
     * @param string[] $streamsConsumed
     */
    public static function refuseEndpointWhoseMessagesCannotBeHandledAtOnce(string $endpointId, int $coroutines, bool $drainsAQueue, array $streamsConsumed): void
    {
        if ($coroutines < 1) {
            throw ConfigurationException::create(
                "Consumer `{$endpointId}` cannot consume on {$coroutines} coroutines: a consumer needs at least one. "
                . 'Pass a positive number as `coroutines` of PollingMetadata, or leave it out to consume one message at a time.'
            );
        }
        if ($coroutines === 1) {
            return;
        }
        if (! $drainsAQueue) {
            throw ConfigurationException::create(
                "Endpoint `{$endpointId}` is set to run on {$coroutines} coroutines, but it runs on a schedule instead of consuming a channel, so every coroutine would run each scheduled execution again. "
                . 'Leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines() for this endpoint. '
                . 'To do its work on coroutines, let it send messages to an #[Asynchronous] handler and run that channel\'s consumer on coroutines.'
            );
        }
        if ($streamsConsumed !== []) {
            $streamsOtherThanTheConsumer = array_diff($streamsConsumed, [$endpointId]);
            $streamsRead = match (count($streamsOtherThanTheConsumer)) {
                0 => 'a stream',
                1 => 'the stream `' . implode('', $streamsOtherThanTheConsumer) . '`',
                default => 'the streams `' . implode('`, `', $streamsOtherThanTheConsumer) . '`',
            };
            throw ConfigurationException::create(
                "Consumer `{$endpointId}` is set to consume on {$coroutines} coroutines, but it reads {$streamsRead} (a Kafka topic, a RabbitMQ stream or an in-memory streaming channel, directly or through a dynamic channel; a dynamic channel with a custom receiving strategy counts as reading every stream channel of the application), where the position of the last handled message is what gets committed. "
                . 'A coroutine finishing a later message would commit past an earlier message another coroutine is still handling, so that message is never handled when the process stops, and the messages are handled out of order. '
                . 'Leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines() for this consumer. '
                . 'To handle more of its messages at once: for a Kafka topic start more processes of this consumer, with at least as many partitions of the topic as processes; a RabbitMQ stream is read in order by one consumer, so let its handler send the work to an #[Asynchronous] queue channel and consume that channel on coroutines. A dynamic channel whose custom receiving strategy reads only queue channels can consume on coroutines when it receives through DynamicMessageChannelBuilder::createRoundRobin(), createRoundRobinWithDifferentChannels() or createThrottlingStrategy() naming those channels.'
            );
        }
    }

    /**
     * @param string[] $streamsConsumed
     * @param Closure(): ConsumerLifecycle $createConsumer
     */
    public static function consume(string $endpointId, int $coroutines, bool $drainsAQueue, array $streamsConsumed, Closure $createConsumer): void
    {
        self::refuseEndpointWhoseMessagesCannotBeHandledAtOnce($endpointId, $coroutines, $drainsAQueue, $streamsConsumed);

        $runtime = self::coroutineRuntime();
        if ($runtime === null) {
            throw ConfigurationException::create(
                "Consumer `{$endpointId}` is set to consume on {$coroutines} coroutines, but this PHP has neither the openswoole nor the swoole extension loaded, so there is nothing to run coroutines on. "
                . 'Install one of the extensions for the PHP that runs the consumer, or consume one message at a time: leave out the `coroutines` option of ecotone:run, ExecutionPollingMetadata::withCoroutines() and PollingMetadata::withCoroutines(). '
                . 'To handle more messages at once without coroutines, start more processes of this consumer.'
            );
        }

        $coroutineClass = $runtime . '\\Coroutine';
        /** @var ConsumerLifecycle[] $consumers */
        $consumers = [];
        /** @var ArrayObject<int, Throwable> $failures */
        $failures = new ArrayObject();
        $consumeOnCoroutines = static function () use ($coroutineClass, $coroutines, $createConsumer, &$consumers, $failures): void {
            $channelClass = $coroutineClass . '\\Channel';
            $finished = new $channelClass($coroutines);
            for ($coroutine = 1; $coroutine <= $coroutines; $coroutine++) {
                $coroutineClass::create(static function () use ($createConsumer, $finished, &$consumers, $failures): void {
                    try {
                        if ($failures->count() === 0) {
                            $consumer = $createConsumer();
                            $consumers[] = $consumer;
                            $consumer->run();
                        }
                    } catch (Throwable $exception) {
                        $failures->append($exception);
                        foreach ($consumers as $consumer) {
                            $consumer->stop();
                        }
                    } finally {
                        $finished->push(true);
                    }
                });
            }
            for ($coroutine = 1; $coroutine <= $coroutines; $coroutine++) {
                $finished->pop();
            }
        };

        if ($coroutineClass::getCid() > 0) {
            $consumeOnCoroutines();
        } else {
            $runtimeClass = $runtime . '\\Runtime';
            $hooksOfTheApplication = $runtimeClass::getHookFlags();
            if ($hooksOfTheApplication === 0) {
                $runtimeClass::enableCoroutine($runtime === 'OpenSwoole' ? $runtimeClass::HOOK_ALL : constant('SWOOLE_HOOK_ALL'));
            }
            try {
                $schedulerClass = $coroutineClass . '\\Scheduler';
                $scheduler = new $schedulerClass();
                $scheduler->add($consumeOnCoroutines);
                $scheduler->start();
            } finally {
                if (self::areHooksSwitchedOffAfterCoroutinesByTheRuntime($runtime)) {
                    $runtimeClass::enableCoroutine($hooksOfTheApplication);
                }
            }
        }

        foreach ($failures as $failure) {
            throw $failure;
        }
    }

    private static function areHooksSwitchedOffAfterCoroutinesByTheRuntime(string $runtime): bool
    {
        return $runtime === 'OpenSwoole';
    }

    private static function coroutineRuntime(): ?string
    {
        return match (true) {
            extension_loaded('openswoole') => 'OpenSwoole',
            extension_loaded('swoole') => 'Swoole',
            default => null,
        };
    }
}
