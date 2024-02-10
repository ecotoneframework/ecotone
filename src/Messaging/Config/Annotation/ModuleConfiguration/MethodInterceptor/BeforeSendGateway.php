<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MethodInterceptor;

use Ecotone\Messaging\Message;

interface BeforeSendGateway
{
    public function execute(Message $message): ?Message;
}
