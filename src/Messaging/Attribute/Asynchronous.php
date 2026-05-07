<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
class Asynchronous
{
    private string|array $channelName;
    /** @var AsynchronousEndpointAttribute[] */
    private array $asynchronousExecution;

    /**
     * @param AsynchronousEndpointAttribute[] $asynchronousExecution Attributes scoped to the asynchronous execution context — applied when the polling consumer processes the Message, not at the synchronous bus call.
     */
    public function __construct(string|array $channelName, array $asynchronousExecution = [])
    {
        Assert::notNullAndEmpty($channelName, 'Channel name can not be empty string');
        Assert::allInstanceOfType($asynchronousExecution, AsynchronousEndpointAttribute::class);
        $this->channelName = $channelName;
        $this->asynchronousExecution = $asynchronousExecution;
    }

    public function getChannelName(): array
    {
        return is_string($this->channelName) ? [$this->channelName] : $this->channelName;
    }

    /**
     * @return AsynchronousEndpointAttribute[]
     */
    public function getAsynchronousExecution(): array
    {
        return $this->asynchronousExecution;
    }
}
