<?php

namespace Ecotone\Dbal\DbalTransaction;

use Enqueue\Dbal\DbalConnectionFactory;

#[\Attribute]
class DbalTransaction
{
    public $connectionReferenceNames = [DbalConnectionFactory::class];
}