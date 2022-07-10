<?php


namespace Test\Ecotone\JMSConverter\Fixture\ExamplesToConvert;


class NullableProperty
{
    private ?int $data;

    public function __construct(?int $data)
    {
        $this->data = $data;
    }
}