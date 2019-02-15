<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\MessageConverter;

use SimplyCodedSoftware\Messaging\Support\Assert;

/**
 * Class GlobalMessageConverterService
 * @package SimplyCodedSoftware\Messaging\MessageConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageConverterRegistry
{
    /**
     * @var MessageConverter[]
     */
    private $messageConverters;

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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(iterable $messageConverters) : self
    {
        Assert::allInstanceOfType($messageConverters, MessageConverter::class);

        return new self($messageConverters);
    }

    /**
     * @return MessageConverter[]
     */
    public function getMessageConverters() : array
    {
        return $this->messageConverters;
    }
}