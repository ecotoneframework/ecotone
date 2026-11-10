<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution\Pulse;

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
final class PulseConsumerFeature
{
    public const GLOBAL = 'global';
    public const CONSUMER_LEVEL = 'consumer-level config';

    private function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly string $scope,
        public readonly ?string $target,
    ) {
    }

    public static function global(string $name, string $value): self
    {
        return new self(name: $name, value: $value, scope: self::GLOBAL, target: null);
    }

    public static function forConsumer(string $consumerOrEndpointId, string $name, string $value): self
    {
        return new self(name: $name, value: $value, scope: self::CONSUMER_LEVEL, target: $consumerOrEndpointId);
    }

    public static function defaultFor(string $consumerOrEndpointId, string $name, string $value): self
    {
        return new self(name: $name, value: $value, scope: self::GLOBAL, target: $consumerOrEndpointId);
    }

    /**
     * @return array{name: string, value: string, scope: string, target: ?string}
     */
    public function toArray(): array
    {
        return ['name' => $this->name, 'value' => $this->value, 'scope' => $this->scope, 'target' => $this->target];
    }
}
