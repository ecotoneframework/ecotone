<?php

namespace Ecotone\Messaging\Gateway;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\MessagingCommands\MessagingCommandsModule;

/**
 * licence Apache-2.0
 */
interface ConsoleCommandRunner
{
    public function execute(#[Header(MessagingCommandsModule::ECOTONE_CONSOLE_COMMAND_NAME)] $commandName, #[Payload] $parameters): mixed;
}
