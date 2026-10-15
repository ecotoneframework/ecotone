<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel\Snapshot;

use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

use JsonException;

/**
 * The framework's own wrapper around the user-serialized model: the position a
 * snapshot covers and the shape it was folded with are written by Ecotone and
 * never become part of the model's own serialization.
 *
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
 */
final class DecisionModelSnapshotEnvelope
{
    private const STATE = 'state';
    private const COVERED_POSITION = 'covered_position';
    private const FOLD_SHAPE = 'fold_shape';

    public function __construct(
        public readonly string $serializedState,
        public readonly int $coveredPosition,
        public readonly string $foldShape,
    ) {
    }

    public function toJson(): string
    {
        return json_encode([
            self::STATE => $this->serializedState,
            self::COVERED_POSITION => $this->coveredPosition,
            self::FOLD_SHAPE => $this->foldShape,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException when the stored document is not an envelope this version wrote
     */
    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($decoded)
            || ! is_string($decoded[self::STATE] ?? null)
            || ! is_int($decoded[self::COVERED_POSITION] ?? null)
            || ! is_string($decoded[self::FOLD_SHAPE] ?? null)
        ) {
            throw new JsonException('Stored document is not a decision model snapshot envelope');
        }

        return new self($decoded[self::STATE], $decoded[self::COVERED_POSITION], $decoded[self::FOLD_SHAPE]);
    }
}
