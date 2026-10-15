<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\Tagging;

use Ecotone\Messaging\Config\ConfigurationException;

use function get_debug_type;
use function is_array;
use function is_bool;
use function is_scalar;
use function preg_match_all;
use function rtrim;
use function sprintf;
use function str_contains;

use Stringable;

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
 */
final class EventTagValueNormalizer
{
    /**
     * @return string[]
     */
    public static function normalize(string $tagName, mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                $result = [...$result, ...self::normalize($tagName, $item)];
            }

            return $result;
        }

        if (is_bool($value)) {
            throw ConfigurationException::create(sprintf(
                "Tag '%s' value must be a string, int, float or Stringable, got bool. Did you mean to compare instead of return?",
                $tagName
            ));
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return [self::validate($tagName, (string) $value)];
        }

        throw ConfigurationException::create(sprintf(
            "Tag '%s' value must be scalar, Stringable, null or an array of those, got %s.",
            $tagName,
            get_debug_type($value)
        ));
    }

    private static function validate(string $tagName, string $value): string
    {
        if ($value === '') {
            throw ConfigurationException::create(sprintf("Tag '%s' value cannot be empty.", $tagName));
        }

        $characterCount = preg_match_all('/./su', $value);
        if ($characterCount === false) {
            throw ConfigurationException::create(sprintf("Tag '%s' value must be valid UTF-8.", $tagName));
        }

        if (str_contains($value, "\0")) {
            throw ConfigurationException::create(sprintf("Tag '%s' value cannot contain a NUL byte.", $tagName));
        }

        if ($characterCount > 255) {
            throw ConfigurationException::create(sprintf("Tag '%s' value cannot be longer than 255 characters, got %d.", $tagName, $characterCount));
        }

        if (rtrim($value) !== $value) {
            throw ConfigurationException::create(sprintf("Tag '%s' value '%s' cannot have trailing whitespace.", $tagName, $value));
        }

        return $value;
    }
}
