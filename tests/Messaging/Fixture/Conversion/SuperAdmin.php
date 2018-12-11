<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Conversion;

/**
 * Class SuperAdmin
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SuperAdmin extends AbstractSuperAdmin implements Admin
{
    /**
     * @inheritDoc
     */
    public function getSuperAdmin()
    {
        // TODO: Implement getUser() method.
    }

    /**
     * @inheritDoc
     */
    public function getAdmin()
    {
        // TODO: Implement getAdmin() method.
    }
}