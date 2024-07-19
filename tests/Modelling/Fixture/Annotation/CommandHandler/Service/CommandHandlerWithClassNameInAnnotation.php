<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;

/**
 * licence Apache-2.0
 */
class CommandHandlerWithClassNameInAnnotation
{
    #[CommandHandler('input', 'command-id')]
    public function execute(): int
    {
        return 1;
    }
}
