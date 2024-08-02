<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Channel\DynamicChannel;

use Ecotone\Messaging\Attribute\InternalHandler;
use Ecotone\Messaging\Attribute\Parameter\Header;
use PHPUnit\Framework\Assert;

final class SimpleConsumptionDecider
{
    public function __construct(private array $results, private string $expectedDynamicChannelName)
    {

    }

    #[InternalHandler('dynamicChannel.decide')]
    public function decide(
        string $channelName,
        #[Header('dynamicChannelName')] $dynamicChannelName
    ): bool {
        Assert::assertSame($this->expectedDynamicChannelName, $dynamicChannelName, 'Dynamic channel name is not equal');

        return $this->results[$channelName];
    }
}
