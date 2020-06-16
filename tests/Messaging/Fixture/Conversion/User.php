<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Conversion;

use Ecotone\Messaging\Annotation\IgnoreDocblockTypeHint;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite;
use Test\Ecotone\Messaging\Fixture\Conversion\Extra\Permission as AdminPermission;

/**
 * Interface Order
 * @package Test\Ecotone\Messaging\Fixture\Conversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface User
{
    public function changeName(string $name) : void;

    /**
     * @param Password $password
     */
    public function changePassword(Password $password) : void;

    /**
     * @param \stdClass $details
     */
    public function changeDetails($details) : void;

    /**
     * @param  array<Test\Ecotone\Messaging\Fixture\Conversion\Extra\Favourite> $favourites
     */
    public function changeFavourites(array $favourites) : void;

    /**
     * @param Favourite $favourite
     */
    public function changeSingleFavourite(Favourite $favourite) : void;

    /**
     * @param Favourite[] $favourites
     */
    public function removeFavourites(array $favourites) : void;

    /**
     * @param  array<Favourite> $favourites
     */
    public function disableFavourites(array $favourites) : void;

    /**
     * @param AdminPermission $adminPermission
     */
    public function addAdminPermission(AdminPermission $adminPermission) : void;

    /**
     * @param int[] $ratings
     */
    public function addRatings(iterable $ratings) : void;

    /**
     * @param int $rating
     */
    public function removeRating(int $rating) : void;

    /**
     * @param int|array $random
     */
    public function randomRating($random) : void;

    /**
     * @param array|string[] $phones
     */
    public function addPhones($phones) : void;

    /**
     * @param Email|Favourite $email
     */
    public function addEmail($email) : void;

    /**
     * @param $surname
     */
    public function changeSurname($surname) : void;

    /**
     * @param mixed $address
     */
    public function changeAddress($address) : void;

    /**
     * @return self
     */
    public function getSelf() : self;

    public function getSelfWithoutDocBlock() : self;

    /**
     * @return static
     */
    public function getStatic();

    public function returnFullUser() : \Test\Ecotone\Messaging\Fixture\Conversion\User;


    public function returnFromGlobalNamespace() : \stdClass;

    /**
     * @param \DateTimeInterface $dateTime
     *
     * @return \DateTimeInterface
     */
    public function interfaceFromGlobalNamespace(\DateTimeInterface $dateTime) : \DateTimeInterface;

    /**
     * @param (\stdClass|string)[] $data
     *
     * @return (\stdClass|string)[]
     * @IgnoreDocblockTypeHint()
     */
    public function ignoreDocblockTypeHint(array $data) : array;

    /**
     * @var mixed[] $data
     * @return mixed[]
     */
    public function mixedArrayCollection(array $data) : array;
}