<?php

declare(strict_types=1);

namespace Test\Ecotone\Lite\Licence;

use DateTimeImmutable;
use DateTimeZone;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Support\LicensingException;

use function json_encode;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\EventRevision\Person;

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
final class LicencingTest extends TestCase
{
    public function test_failing_on_licence_not_encoded(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrap([
            Person::class,
        ], licenceKey: 'incorrect');
    }

    #[DataProvider('licenceWithMissingField')]
    public function test_failing_on_licence_data_not_having_required_fields(string $licenceKey): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrap([
            Person::class,
        ], licenceKey: base64_encode($licenceKey));
    }

    public function test_valid_licence(): void
    {
        EcotoneLite::bootstrap(
            [
                Person::class,
            ],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([ModulePackageList::CORE_PACKAGE]),
            licenceKey: $this->generate(
                (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+30 seconds'),
                false
            )
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_failing_on_expired_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrap(
            [
                Person::class,
            ],
            licenceKey: $this->generate(
                (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-30 seconds'),
                false
            )
        );
    }

    public function test_licence_for_enterprise_plus_is_valid_when_expires_after_package_release_time(): void
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../../composer.json'), true, JSON_THROW_ON_ERROR);
        $releaseTime = new DateTimeImmutable(trim($composer['extra']['release-time']), new DateTimeZone('UTC'));

        EcotoneLite::bootstrap(
            [
                Person::class,
            ],
            configuration: ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([ModulePackageList::CORE_PACKAGE]),
            licenceKey: $this->generate(
                $releaseTime->modify('+30 seconds'),
                true
            )
        );

        $this->expectNotToPerformAssertions();
    }

    public function test_licence_for_enterprise_plus_is_invalid_when_expires_before_package_release_time(): void
    {
        $this->expectException(LicensingException::class);

        $composer = json_decode(file_get_contents(__DIR__ . '/../../../composer.json'), true, JSON_THROW_ON_ERROR);
        $releaseTime = new DateTimeImmutable(trim($composer['extra']['release-time']), new DateTimeZone('UTC'));

        EcotoneLite::bootstrap(
            [
                Person::class,
            ],
            licenceKey: $this->generate(
                $releaseTime->modify('-30 seconds'),
                true
            )
        );

        $this->expectNotToPerformAssertions();
    }

    public static function licenceWithMissingField(): iterable
    {
        yield 'missing email' => [
            json_encode([
                'data' => [
                    'expireAt' => '2021-01-01 00:00:00',
                    'isEnterprisePlus' => false,
                ],
                'signature' => 'test',
            ]),
        ];
        yield 'missing expire at' => [
            json_encode([
                'data' => [
                    'email' => 'test@wp.pl',
                    'isEnterprisePlus' => false,
                ],
                'signature' => 'test',
            ]),
        ];
        yield 'missing enterprise plus details' => [
            json_encode([
                'data' => [
                    'email' => 'test@wp.pl',
                    'expireAt' => '2021-01-01 00:00:00',
                ],
                'signature' => 'test',
            ]),
        ];
        yield 'missing signature' => [
            json_encode([
                'data' => [
                    'email' => 'test@wp.pl',
                    'expireAt' => '2021-01-01 00:00:00',
                    'isEnterprisePlus' => false,
                ],
            ]),
        ];
        yield 'wrong signature without encoding' => [
            json_encode([
                'data' => [
                    'email' => 'test@wp.pl',
                    'expireAt' => '2021-01-01 00:00:00',
                    'isEnterprisePlus' => false,
                ],
                'signature' => 'test',
            ]),
        ];
        yield 'wrong signature with encoding' => [
            json_encode([
                'data' => [
                    'email' => 'test@wp.pl',
                    'expireAt' => '2021-01-01 00:00:00',
                    'isEnterprisePlus' => false,
                ],
                'signature' => base64_encode('test'),
            ]),
        ];
    }

    public function generate(
        DateTimeImmutable $expirationAtTheEndOfDay,
        bool $isEnterprisePlus,
    ): string {
        $privateKey = openssl_pkey_get_private(file_get_contents(__DIR__ . '/private_key.pem'));
        $data = [
            'email' => 'test@wp.pl',
            'expireAt' => $expirationAtTheEndOfDay->format('Y-m-d H:i:s'),
            'isEnterprisePlus' => $isEnterprisePlus,
        ];

        openssl_sign(
            data: json_encode($data, JSON_THROW_ON_ERROR),
            signature: $signature,
            private_key: $privateKey,
            algorithm: OPENSSL_ALGO_SHA256,
        );

        return base64_encode(json_encode([
            'signature' => base64_encode($signature),
            'data' => $data,
        ], JSON_THROW_ON_ERROR));
    }
}
