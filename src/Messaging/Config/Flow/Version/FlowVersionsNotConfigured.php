<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Flow\Version;

use Ecotone\Messaging\Config\ConfigurationException;

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
final class FlowVersionsNotConfigured
{
    public function store(string $label, bool $replace = false): void
    {
        throw self::refusalOf('ecotone:flow:store');
    }

    public function versions(): void
    {
        throw self::refusalOf('ecotone:flow:versions');
    }

    public function compare(): void
    {
        throw self::refusalOf('ecotone:flow:compare');
    }

    private static function refusalOf(string $command): ConfigurationException
    {
        return ConfigurationException::create("{$command} needs a location to keep flow versions in, but none is configured. Return FlowVersionsConfiguration::createWithLocation(__DIR__ . '/../flow-versions') from a #[ServiceContext] method, pointing at a directory committed with the application.");
    }
}
