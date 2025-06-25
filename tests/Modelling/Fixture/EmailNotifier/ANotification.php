<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EmailNotifier;

class ANotification implements EmailNotification
{
    public function __construct(
        private string $to = '',
    ) {
    }
}
