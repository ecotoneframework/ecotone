<?php


namespace TestingNamespace\Correct;

use Ecotone\Messaging\Annotation\ApplicationContext;

class CorrectClass
{
    /**
     * @return array
     * @ApplicationContext()
     */
    public function someExtension() : array
    {
        return [];
    }
}