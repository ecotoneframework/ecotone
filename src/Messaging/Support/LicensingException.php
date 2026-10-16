<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\MessagingException;

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
final class LicensingException extends MessagingException
{
    public static function create(string $message, ?int $errorCode = null): static
    {
        return new static(
            $message
            . ' To evaluate Enterprise features, start a trial at https://ecotone.tech/pricing or email us at "support@simplycodedsoftware.com" for a trial key. Production licenses are available at https://ecotone.tech.'
            . " Pass the key with the licenceKey: argument of EcotoneLite::bootstrap() (Ecotone Lite), 'licenceKey' in config/ecotone.php (Laravel), ecotone.licenceKey in your Symfony configuration, or the licenceKey argument of EcotoneConfig (Tempest).",
            is_null($errorCode) ? static::errorCode() : $errorCode
        );
    }

    protected static function errorCode(): int
    {
        return self::LICENSE_EXCEPTION;
    }
}
