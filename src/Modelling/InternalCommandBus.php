<?php


namespace Ecotone\Modelling;

use Ecotone\Messaging\Annotation\Gateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Headers;
use Ecotone\Messaging\Annotation\Parameter\Payload;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\LazyEventBus\LazyEventPublishing;

/**
 * Command bus without event publishing. Can be used inside command handler
 * @package Ecotone\Modelling
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface InternalCommandBus extends CommandBus
{
}