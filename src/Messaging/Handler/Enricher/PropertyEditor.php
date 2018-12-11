<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Enricher;

use SimplyCodedSoftware\Messaging\Message;

/**
 * Interface PropertySetter
 * @package SimplyCodedSoftware\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface PropertyEditor
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