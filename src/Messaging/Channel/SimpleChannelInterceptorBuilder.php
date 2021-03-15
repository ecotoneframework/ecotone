<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ReferenceSearchService;

/**
 * Class SimpleChannelInterceptorBuilder
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    private int $precedence;
    private string $channelName;
    private string $referenceName;
    private ?object $directObject = null;

    /**
     * SimpleChannelInterceptorBuilder constructor.
     * @param int $precedence
     * @param string $channelName
     * @param string $referenceName
     */
    private function __construct(int $precedence, string $channelName, string $referenceName)
    {
        $this->precedence = $precedence;
        $this->channelName = $channelName;
        $this->referenceName = $referenceName;
    }

    /**
     * @param string $channelName
     * @param string $referenceName
     * @return SimpleChannelInterceptorBuilder
     */
    public static function create(string $channelName, string $referenceName) : self
    {
        return new self(0, $channelName, $referenceName);
    }

    public static function createWithDirectObject(string $channelName, object $object) : self
    {
        $self = new self(0, $channelName, "");
        $self->directObject = $object;

        return $self;
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
    public function withPrecedence(int $precedence) : self
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

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->referenceName ? [$this->referenceName] : [];
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        return $this->directObject ? $this->directObject : $referenceSearchService->get($this->referenceName);
    }
}