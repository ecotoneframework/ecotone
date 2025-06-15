<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

/**
 * licence Enterprise
 */
class ComplexCommand
{
    public function __construct(
        public string $email
    ) {
    }
}
