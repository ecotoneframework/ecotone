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
final class PulseChannelDepth
{
    private function __construct(
        public readonly ?int $ready,
        public readonly ?int $delayed,
        public readonly ?int $inFlight,
        public readonly ?int $consumers,
        public readonly ?string $probeError,
        public readonly ?bool $setUp,
    ) {
    }

    public static function measured(int $ready, ?int $delayed = null, ?int $inFlight = null, ?int $consumers = null, ?bool $setUp = null): self
    {
        return new self($ready, $delayed, $inFlight, $consumers, null, $setUp);
    }

    public static function unmeasured(): self
    {
        return new self(null, null, null, null, null, null);
    }

    public static function failed(string $reason): self
    {
        return new self(null, null, null, null, $reason, null);
    }

    public static function missing(): self
    {
        return new self(null, null, null, null, null, false);
    }

    /**
     * @return array{ready: int|null, delayed: int|null, inFlight: int|null, consumers: int|null, probeError: string|null, setUp: bool|null}
     */
    public function toArray(): array
    {
        return ['ready' => $this->ready, 'delayed' => $this->delayed, 'inFlight' => $this->inFlight, 'consumers' => $this->consumers, 'probeError' => $this->probeError, 'setUp' => $this->setUp];
    }
}
