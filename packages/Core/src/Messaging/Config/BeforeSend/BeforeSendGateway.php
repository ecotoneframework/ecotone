<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\BeforeSend;

use Ecotone\Messaging\Message;

interface BeforeSendGateway
{
    public function execute(Message $message) : ?Message;
}