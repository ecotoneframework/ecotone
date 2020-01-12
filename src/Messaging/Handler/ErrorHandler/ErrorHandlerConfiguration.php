<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ErrorHandler;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\MessageHandler;

class ErrorHandlerConfiguration
{
    /**
     * @var string
     */
    private $errorChannelName;
    /**
     * @var RetryTemplate
     */
    private $retryTemplate;
    /**
     * @var string|null
     */
    private $deadLetterChannel;

    private function __construct(string $errorChannelName, RetryTemplate $retryTemplateBuilder, ?string $deadLetterChannel)
    {
        $this->deadLetterChannel = $deadLetterChannel;
        $this->retryTemplate = $retryTemplateBuilder;
        $this->errorChannelName = $errorChannelName;
    }

    public static function create(string $errorChannelName, RetryTemplateBuilder $retryTemplate) : self
    {
        return new self($errorChannelName, $retryTemplate->build(), null);
    }

    public static function createWithDeadLetterChannel(string $errorChannelName, RetryTemplateBuilder $retryTemplate, string $deadLetterChannel)
    {
        return new self($errorChannelName, $retryTemplate->build(), $deadLetterChannel);
    }

    /**
     * @return string
     */
    public function getErrorChannelName(): string
    {
        return $this->errorChannelName;
    }

    public function getDeadLetterQueueChannel(): ?string
    {
        return $this->deadLetterChannel;
    }

    public function getRetryTemplate(): RetryTemplate
    {
        return $this->retryTemplate;
    }
}