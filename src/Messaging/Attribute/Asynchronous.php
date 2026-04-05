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
    private array $endpointAnnotations;

    /**
     * @param AsynchronousEndpointAttribute[] $endpointAnnotations
     */
    public function __construct(string|array $channelName, array $endpointAnnotations = [])
    {
        Assert::notNullAndEmpty($channelName, 'Channel name can not be empty string');
        Assert::allInstanceOfType($endpointAnnotations, AsynchronousEndpointAttribute::class);
        $this->channelName = $channelName;
        $this->endpointAnnotations = $endpointAnnotations;
    }

    public function getChannelName(): array
    {
        return is_string($this->channelName) ? [$this->channelName] : $this->channelName;
    }

    /**
     * @return AsynchronousEndpointAttribute[]
     */
    public function getEndpointAnnotations(): array
    {
        return $this->endpointAnnotations;
    }
}
