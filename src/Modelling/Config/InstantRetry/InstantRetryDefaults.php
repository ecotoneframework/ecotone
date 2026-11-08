<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Modelling\Config\DatabaseTransaction\DatabaseTransactionRegistration;

/**
 * licence Apache-2.0
 */
final class InstantRetryDefaults
{
    private function __construct(
        public readonly bool $apply,
        public readonly DatabaseTransactionRegistration $databaseTransactions,
    ) {
    }

    public static function from(array $extensionObjects): self
    {
        $testConfiguration = ExtensionObjectResolver::resolve(TestConfiguration::class, $extensionObjects)[0] ?? null;

        return new self(
            apply: $testConfiguration === null || $testConfiguration->defaultInstantRetries,
            databaseTransactions: ExtensionObjectResolver::resolveUnique(DatabaseTransactionRegistration::class, $extensionObjects, DatabaseTransactionRegistration::none()),
        );
    }

    public function forAsynchronousEndpoints(): bool
    {
        return $this->apply && $this->databaseTransactions->onAsynchronousEndpoints;
    }

    public function forCommandBus(): bool
    {
        return $this->apply && $this->databaseTransactions->onCommandBus;
    }
}
