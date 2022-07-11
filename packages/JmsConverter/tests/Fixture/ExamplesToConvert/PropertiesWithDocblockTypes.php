<?php

namespace Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert;

class PropertiesWithDocblockTypes
{
    private string $name;
    private string $surname;

    /**
     * ObjectWithDocblockTypes constructor.
     * @param string $name
     * @param string $surname
     */
    public function __construct(string $name, string $surname)
    {
        $this->name = $name;
        $this->surname = $surname;
    }
}
