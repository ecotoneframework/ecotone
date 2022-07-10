<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Message;

/**
 * Interface PropertySetter
 * @package Ecotone\Messaging\Handler\Enricher
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