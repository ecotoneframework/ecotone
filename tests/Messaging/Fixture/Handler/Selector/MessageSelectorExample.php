<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Selector;

use Ecotone\Messaging\Message;
use stdClass;

/**
 * Class TrueMessageSelector
 * @package Test\Ecotone\Messaging\Fixture\Handler\Selector
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MessageSelectorExample
{
    private function __construct()
    {
    }

    /**
     * @return MessageSelectorExample
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function accept(Message $message): bool
    {
        return false;
    }

    /**
     * @param Message $message
     *
     * @return bool
     */
    public function refuse(Message $message): bool
    {
        return true;
    }

    /**
     * @return stdClass
     */
    public function wrongReturnType(): stdClass
    {
        return new stdClass();
    }
}
