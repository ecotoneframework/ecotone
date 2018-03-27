<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher;

use SimplyCodedSoftware\IntegrationMessaging\Message;

/**
 * Interface PropertySetter
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface Setter
{
    /**
     * @param Message $enrichMessage
     * @param Message|null $replyMessage
     *
     * @return mixed new payload
     */
    public function evaluate(Message $enrichMessage, ?Message $replyMessage);

    /**
     * @return bool
     */
    public function isPayloadSetter() : bool;
}