<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class SimpleChannelInterceptorBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SimpleChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    /**
     * @var int
     */
    private $importanceOrder;
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
     * @param int $orderNumber
     * @param string $channelName
     * @param string $referenceName
     */
    private function __construct(int $orderNumber, string $channelName, string $referenceName)
    {
        $this->importanceOrder = $orderNumber;
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
     * @param int $importance
     * @return SimpleChannelInterceptorBuilder
     */
    public function withImportance(int $importance) : self
    {
        $this->importanceOrder = $importance;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getImportanceOrder(): int
    {
        return $this->importanceOrder;
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
        return $referenceSearchService->findByReference($this->referenceName);
    }
}