<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Channel;

use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;

/**
 * Class SimpleChannelInterceptorBuilder
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    /**
     * @var int
     */
    private $precedence;
    /**
     * @var string
     */
    private $channelName;
    /**
     * @var string
     */
    private $referenceName;

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
        return [
            $this->referenceName
        ];
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        return $referenceSearchService->get($this->referenceName);
    }
}