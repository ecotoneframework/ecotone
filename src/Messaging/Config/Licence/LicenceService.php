<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Licence;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Messaging\Support\LicensingException;

use function json_encode;

/**
 * licence Enterprise
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
