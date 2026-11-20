<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;
use Test\Ecotone\Messaging\Unit\Handler\PreferredChannel;
use Test\Ecotone\Messaging\Unit\Handler\ReferenceByQuery;
use Test\Ecotone\Messaging\Unit\Handler\TenantChannels;

/**
 * licence Apache-2.0
 */
final class ConvertedClosureExpressionService
{
    public ?PreferredChannel $channel = null;

    #[CommandHandler('channel.fromReferenceClosure')]
    public function fromReferenceClosure(
        #[Reference(TenantChannels::class, expression: static function (#[Header('tenantId')] string $tenantId, #[Reference] TenantChannels $tenantChannels): string {
            return $tenantChannels->channelNameOf($tenantId);
        })] PreferredChannel $channel,
    ): void {
        $this->channel = $channel;
    }

    #[CommandHandler('channel.fromHeaderClosure')]
    public function fromHeaderClosure(
        #[Header('preferences', expression: static function (#[Header('preferences')] array $preferences): string {
            return $preferences['channel'];
        })] PreferredChannel $channel,
    ): void {
        $this->channel = $channel;
    }

    #[CommandHandler('channel.fromPayloadClosure')]
    public function fromPayloadClosure(
        #[Payload(expression: static function (array $payload): string {
            return $payload['channel'];
        })] PreferredChannel $channel,
    ): void {
        $this->channel = $channel;
    }

    #[CommandHandler('channel.fromQuery')]
    public function fromQuery(#[ReferenceByQuery('tenant.channelName')] PreferredChannel $channel): void
    {
        $this->channel = $channel;
    }

    #[QueryHandler('tenant.channelName')]
    public function channelName(#[Header('tenantId')] string $tenantId): string
    {
        return $tenantId === 'tenant-a' ? 'sms' : 'email';
    }
}
