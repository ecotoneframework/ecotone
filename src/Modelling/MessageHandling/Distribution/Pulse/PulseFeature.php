<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Pulse\PulseConfiguration;
use Ecotone\Messaging\Attribute\EndpointAnnotation;
use Ecotone\Messaging\Attribute\IdentifiedAnnotation;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
final class PulseFeature
{
    /**
     * @param array<string, string> $settings
     * @param list<string> $handlers
     */
    public function __construct(
        public readonly string $package,
        public readonly string $name,
        public readonly bool $enabled,
        public readonly array $settings,
        public readonly array $handlers,
        public readonly string $hint,
        public readonly string $configuration,
    ) {
    }

    /**
     * @param object[] $serviceExtensions
     */
    public static function isReportedFor(array $serviceExtensions): bool
    {
        return ExtensionObjectResolver::resolve(PulseConfiguration::class, $serviceExtensions) !== [];
    }

    /**
     * @param list<string> $publishers
     */
    public static function messagePublishers(string $package, string $broker, array $publishers, string $configuration): self
    {
        return new self(
            package: $package,
            name: $broker . ' message publishers',
            enabled: $publishers !== [],
            settings: ['Publishers' => (string) count($publishers)],
            handlers: $publishers,
            hint: $publishers === []
                ? sprintf('No message publisher sends to %s, so the application reaches it only through message channels.', $broker)
                : sprintf('The application sends messages straight to %s through each MessagePublisher listed here, fetched by its reference name, to the destination named next to it. High-throughput publishing batches the sends and confirms them without blocking: https://docs.ecotone.tech/modelling/microservices-php/message-publisher', $broker),
            configuration: $configuration,
        );
    }

    /**
     * @param string[] $exceptionClasses
     */
    public static function exceptionsOf(array $exceptionClasses): string
    {
        return $exceptionClasses === [] ? 'any exception' : implode(', ', array_map(self::shortNameOf(...), $exceptionClasses));
    }

    /**
     * @param string[] $exceptionClasses
     */
    public static function exceptionClassesOf(array $exceptionClasses): string
    {
        return '[' . implode(', ', array_map(static fn (string $class): string => self::shortNameOf($class) . '::class', $exceptionClasses)) . ']';
    }

    public static function handlerOf(AnnotatedFinding $registration): string
    {
        $routingKey = '';
        foreach ($registration->getMethodAnnotationsWithType(IdentifiedAnnotation::class) as $handler) {
            $routingKey = match (true) {
                $handler instanceof EventHandler => $handler->listenTo,
                $handler instanceof EndpointAnnotation => $handler->getInputChannelName(),
                default => '',
            };
            if ($routingKey !== '') {
                break;
            }
        }

        return ($routingKey === '' ? self::shortNameOf($registration->getClassName()) : $routingKey) . ' → ' . $registration->getMethodName();
    }

    public static function durationOf(int $milliseconds): string
    {
        foreach (['day' => 86_400_000, 'hour' => 3_600_000, 'minute' => 60_000, 'second' => 1000] as $unit => $length) {
            if ($milliseconds >= $length && $milliseconds % $length === 0) {
                $count = intdiv($milliseconds, $length);

                return $count . ' ' . $unit . ($count === 1 ? '' : 's');
            }
        }

        return $milliseconds . ' ms';
    }

    public static function retryScheduleOf(RetryTemplate $retryTemplate): string
    {
        $shown = 5;
        $delays = [];
        for ($retryNumber = 1; $retryNumber <= $shown && $retryTemplate->canBeCalledNextTime($retryNumber); $retryNumber++) {
            $delays[] = $retryTemplate->calculateNextDelay($retryNumber) . ' ms';
        }
        $unlimited = 1000;
        $retries = 0;
        while ($retries < $unlimited && $retryTemplate->canBeCalledNextTime($retries + 1)) {
            $retries++;
        }

        if ($delays === []) {
            return 'no retry';
        }
        $schedule = count($delays) === 1 ? $delays[0] : implode(', ', array_slice($delays, 0, -1)) . ' and ' . end($delays);
        if ($retries === $unlimited) {
            $firstDelay = $retryTemplate->calculateNextDelay(1);

            return 'retries without a limit: after ' . implode(', ', $delays) . ($firstDelay > 0 ? ', each next delay ' . intdiv($retryTemplate->calculateNextDelay(2), $firstDelay) . ' times longer' : '');
        }

        return sprintf('%d %s: after %s%s', $retries, $retries === 1 ? 'retry' : 'retries', $schedule, $retries > $shown ? ', …' : '');
    }

    public static function retriesOf(int $retryTimes): string
    {
        return $retryTimes . ($retryTimes === 1 ? ' retry' : ' retries');
    }

    public static function onOff(bool $isOn): string
    {
        return $isOn ? 'on' : 'off';
    }

    public static function shortNameOf(string $className): string
    {
        $position = strrpos($className, '\\');

        return str_contains($className, '@anonymous') ? 'class@anonymous' : ($position === false ? $className : substr($className, $position + 1));
    }

    /**
     * @return array{name: string, enabled: bool, settings: array<string, string>, handlers: list<string>, hint: string, configuration: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'settings' => $this->settings,
            'handlers' => $this->handlers,
            'hint' => $this->hint,
            'configuration' => $this->configuration,
        ];
    }
}
