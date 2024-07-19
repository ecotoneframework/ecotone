<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Splitter;

/**
 * Class WrongSplittingService
 * @package Test\Ecotone\Messaging\Fixture\Handler\Splitter
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class WrongSplittingService
{
    public function splittingWithReturnString(): string
    {
        return 'some';
    }
}
