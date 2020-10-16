<?php


namespace Incorrect\TestingNamespace;


use Ecotone\Messaging\Annotation\ApplicationContext;

class CorrectNamespace
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