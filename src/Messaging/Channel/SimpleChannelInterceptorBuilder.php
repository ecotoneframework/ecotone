<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;

use function is_string;

/**
 * Class SimpleChannelInterceptorBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SimpleChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    private function __construct(private int $precedence, private string $channelName, private $referenceName)
    {
    }

    public static function create(string $channelName, string $referenceName): self
    {
        return new self(0, $channelName, $referenceName);
    }

    /**
     * @inheritDoc
     */
    public function relatedChannelName(): string
    {
        return $this->channelName;
    }

    /**
     * @param int $precedence
     * @return SimpleChannelInterceptorBuilder
     */
    public function withPrecedence(int $precedence): self
    {
        $this->precedence = $precedence;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrecedence(): int
    {
        return $this->precedence;
    }

    public function compile(MessagingContainerBuilder $builder): Reference
    {
        return is_string($this->referenceName) ? Reference::to($this->referenceName) : $this->referenceName;
    }
}
