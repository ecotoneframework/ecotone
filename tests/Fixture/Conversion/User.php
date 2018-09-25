<?php
declare(strict_types=1);

namespace Fixture\Conversion;

/**
 * Interface Order
 * @package Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface User
{
    public function changeName(string $name) : void;

    /**
     * @param Password $password
     */
    public function changePassword(Password $password) : void;
}