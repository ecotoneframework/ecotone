<?php

namespace Ecotone\Dbal\DbalTransaction;

use Attribute;
use Enqueue\Dbal\DbalConnectionFactory;

#[Attribute]
class DbalTransaction
{
    public $connectionReferenceNames = [DbalConnectionFactory::class];
}
