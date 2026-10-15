<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Licence;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Messaging\Support\LicensingException;

use function json_encode;

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
final class LicenceService
{
    public function validate(string $licenceKey): void
    {
        if (! extension_loaded('openssl')) {
            throw LicensingException::create('OpenSSL extension is required for licencing. Please install `ext-openssl` before using.');
        }

        $publicKey = openssl_pkey_get_public(file_get_contents(__DIR__ . '/key.pem'));
        $licence = \json_decode(base64_decode($licenceKey), true, JSON_THROW_ON_ERROR);

        if (! isset($licence['signature']) || ! isset($licence['data'])) {
            throw LicensingException::create('Invalid licence key provided. Please contact us at: "support@simplycodedsoftware.com"');
        }

        $result = openssl_verify(
            data: json_encode($licence['data'], JSON_THROW_ON_ERROR),
            signature: base64_decode($licence['signature']),
            public_key: $publicKey,
            algorithm: OPENSSL_ALGO_SHA256
        );

        if ($result !== 1) {
            throw LicensingException::create('Invalid licence key provided. Please contact us at: "support@simplycodedsoftware.com"');
        };

        /** @var array{email: string, expireAt: string, isEnterprisePlus: bool} $data */
        $data = $licence['data'];
        $licenceExpirationTime = new DateTimeImmutable($data['expireAt'], new DateTimeZone('UTC'));

        if ($data['isEnterprisePlus']) {
            $mainComposer = json_decode(file_get_contents(__DIR__.'/../../../../composer.json'), true, JSON_THROW_ON_ERROR);
            $versionReleaseAt = new DateTimeImmutable(trim($mainComposer['extra']['release-time']), new DateTimeZone('UTC'));

            if ($licenceExpirationTime < $versionReleaseAt) {
                throw LicensingException::create(sprintf('Licence has expired at %s UTC. Please renew the licence or contact us at: "support@simplycodedsoftware.com"', $licenceExpirationTime->format('Y-m-d H:i:s')));
            }

            return;
        }

        $currentTime = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if ($licenceExpirationTime < $currentTime) {
            throw LicensingException::create(sprintf('Licence has expired at %s UTC. Please renew the licence or contact us at: "support@simplycodedsoftware.com"', $licenceExpirationTime->format('Y-m-d H:i:s')));
        }
    }
}
