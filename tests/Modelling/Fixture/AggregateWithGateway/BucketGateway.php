<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateWithGateway;

use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\Payload;
use Ramsey\Uuid\UuidInterface;

/**
 * licence Apache-2.0
 */
interface BucketGateway
{
    #[MessageGateway(Bucket::ADD)]
    public function add(#[Identifier] UuidInterface $bucketId, #[Payload] array $command): void;

    #[MessageGateway(Bucket::GET)]
    public function get(#[Identifier] UuidInterface $bucketId, #[Payload] UuidInterface $key): string;

    #[MessageGateway(Bucket::GET)]
    public function getWithoutAggregateIdentifier(#[Payload] UuidInterface $key): string;
}
