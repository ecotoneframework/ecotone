<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\HeaderConversion;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ramsey\Uuid\UuidInterface;

final class ConvertedHeaderEndpoint
{
    private mixed $result;

    #[Asynchronous('async')]
    #[CommandHandler('withScalarConversion', endpointId: 'withScalarConversionEndpoint')]
    public function handleWithScalarConversion(
        #[Header('token')] UuidInterface $token
    ) {
        $this->result = $token;
    }

    #[Asynchronous('async')]
    #[CommandHandler('withFallbackConversion', endpointId: 'withFallbackConversionEndpoint')]
    public function handleWithFallbackConversion(
        #[Header('tokens')] array $tokens
    ) {
        $this->result = $tokens;
    }

    public function result(): mixed
    {
        return $this->result;
    }
}
