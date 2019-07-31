<?php
declare(strict_types=1);

namespace FixtureIncorrectNamespace\Wrong;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;

/**
 * Class ClassWithIncorrectNamespace
 * @package FixtureIncorrectNamespace
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ClassWithIncorrectNamespace
{
    /**
     * @return array
     * @Extension()
     */
    public function someExtension() : array
    {
        return [];
    }
}