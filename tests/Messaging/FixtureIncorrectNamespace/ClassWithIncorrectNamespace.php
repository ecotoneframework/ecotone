<?php
declare(strict_types=1);

namespace FixtureIncorrectNamespace\Wrong;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Extension;

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