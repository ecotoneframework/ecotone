<?php


namespace Ecotone\Modelling\Annotation;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_CLASS)]
class MessageName
{
    private string $name;

    public function __construct(string $name)
    {
        Assert::notNullAndEmpty($name, "Message name can not be empty");
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}