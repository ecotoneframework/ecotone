<?php


namespace Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert;


class PropertyWithNullUnionType
{
    private ?string $data;

    /**
     * PropertyWithNullUnionType constructor.
     * @param string|null $data
     */
    public function __construct(?string $data)
    {
        $this->data = $data;
    }
}