<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Interface MessageHandler
 * @package SimplyCodedSoftware\IntegrationMessaging
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface MessageHandler
{
    /**
     * Handle given message
     *
     * @param Message $message
     * @throws \Exception
     */
    public function handle(Message $message) : void;
}