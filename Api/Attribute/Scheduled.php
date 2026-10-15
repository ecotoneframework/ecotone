<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\ChannelAdapter;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Scheduled extends ChannelAdapter
{
    public readonly string $requestChannelName;
    private array $requiredInterceptorNames;

    public function __construct(string $requestChannelName = NullableMessageChannel::CHANNEL_NAME, string $endpointId = '', array $requiredInterceptorNames = [])
    {
        Assert::notNullAndEmpty($requestChannelName, 'Request channel name can not be empty for scheduled');
        parent::__construct($endpointId);

        $this->requestChannelName = $requestChannelName;
        $this->requiredInterceptorNames = $requiredInterceptorNames;
    }

    public function getRequiredInterceptorNames(): array
    {
        return $this->requiredInterceptorNames;
    }
}
