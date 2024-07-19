<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateWithGateway;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ramsey\Uuid\UuidInterface;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Bucket
{
    public const ADD = 'bucket.add';
    public const GET = 'bucket.get';
    public const CREATE = 'bucket.create';

    /** @var array<string, string> */
    private array $bucket = [];

    private function __construct(#[Identifier] public UuidInterface $bucketId)
    {
    }

    #[CommandHandler(self::CREATE)]
    public static function create(UuidInterface $bucketId): self
    {
        return new self($bucketId);
    }

    #[CommandHandler(self::ADD)]
    public function add(array $command): void
    {
        $this->bucket = array_merge($this->bucket, $command);
    }

    #[QueryHandler(self::GET)]
    public function get(UuidInterface $key): string
    {
        return $this->bucket[$key->toString()];
    }
}
