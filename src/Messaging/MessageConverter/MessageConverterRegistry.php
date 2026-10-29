<?php

declare(strict_types=1);

namespace Ecotone\Messaging\MessageConverter;

use Ecotone\Messaging\Support\Assert;

/**
 * Class GlobalMessageConverterService
 * @package Ecotone\Messaging\MessageConverter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MessageConverterRegistry
{
    /**
     * @var MessageConverter[]
     */
    private iterable $messageConverters;

    /**
     * DefaultMessageConverterService constructor.
     * @param MessageConverter[] $messageConverters
     */
    private function __construct(iterable $messageConverters)
    {
        $this->messageConverters = $messageConverters;
    }

    /**
     * @param MessageConverter[] $messageConverters
     * @return MessageConverterRegistry
     * @throws \Ecotone\Messaging\MessagingException
     */
    public static function createWith(iterable $messageConverters): self
    {
        Assert::allInstanceOfType($messageConverters, MessageConverter::class);

        return new self($messageConverters);
    }

    /**
     * @return MessageConverter[]
     */
    public function getMessageConverters(): array
    {
        return $this->messageConverters;
    }
}
