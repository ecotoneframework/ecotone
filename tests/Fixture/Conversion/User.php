<?php
declare(strict_types=1);

namespace Fixture\Conversion;

use Fixture\Conversion\Extra\Favourite;
use Fixture\Conversion\Extra\Permission as AdminPermission;

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

    /**
     * @param \stdClass $details
     */
    public function changeDetails($details) : void;

    /**
     * @param  array<Fixture\Conversion\Extra\Favourite> $favourites
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
}