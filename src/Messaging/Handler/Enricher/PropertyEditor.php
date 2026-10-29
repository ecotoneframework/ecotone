<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Enricher;

use Ecotone\Messaging\Message;

/**
 * Interface PropertySetter
 * @package Ecotone\Messaging\Handler\Enricher
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
    public function isPayloadSetter(): bool;
}
