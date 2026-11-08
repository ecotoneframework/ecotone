<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\DatabaseTransaction;

/**
 * licence Apache-2.0
 */
final class DatabaseTransactionRegistration
{
    /**
     * @param string[] $asynchronousEndpointsWithoutTransaction
     */
    public function __construct(
        public readonly bool $onCommandBus,
        public readonly bool $onAsynchronousEndpoints,
        public readonly array $asynchronousEndpointsWithoutTransaction,
    ) {
    }

    public static function none(): self
    {
        return new self(onCommandBus: false, onAsynchronousEndpoints: false, asynchronousEndpointsWithoutTransaction: []);
    }
}
