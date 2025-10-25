<?php

/*
 * licence Apache-2.0
 */

namespace Ecotone\Messaging\Scheduling;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

use function is_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

use RuntimeException;

use function sprintf;

class DatePoint extends DateTimeImmutable
{
    public function unixTime(): Duration
    {
        return Duration::microseconds(
            $this->getTimestamp() * 1_000_000 + $this->getMicrosecond()
        );
    }

    public function setUnixTime(Duration $duration): static
    {
        $microseconds = $duration->inMicroseconds();
        $timestamp = (int) ($microseconds / 1_000_000);
        $microsecond = $microseconds % 1_000_000;

        return $this->setTimestamp($timestamp)->setMicrosecond($microsecond);
    }

    /**
     * @throws RuntimeException When $format or $datetime are invalid
     */
    public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): static
    {
        return parent::createFromFormat($format, $datetime, $timezone) ?: throw new RuntimeException(static::getLastErrors()['errors'][0] ?? 'Invalid date string or format.');
    }

    public static function createFromInterface(DateTimeInterface $object): static
    {
        return parent::createFromInterface($object);
    }

    public static function createFromMutable(DateTime $object): static
    {
        return parent::createFromMutable($object);
    }

    public static function createFromTimestamp(int|float $timestamp): static
    {
        //        Not passing phpstan check
        //        if (\PHP_VERSION_ID >= 80400) {
        //            return parent::createFromTimestamp($timestamp);
        //        }

        if (is_int($timestamp) || ! $ms = (int) $timestamp - $timestamp) {
            return static::createFromFormat('U', (string) $timestamp);
        }

        if (! is_finite($timestamp) || PHP_INT_MAX + 1.0 <= $timestamp || PHP_INT_MIN > $timestamp) {
            throw new InvalidArgumentException(sprintf('DateTimeImmutable::createFromTimestamp(): Argument #1 ($timestamp) must be a finite number between %s and %s.999999, %s given', PHP_INT_MIN, PHP_INT_MAX, $timestamp));
        }

        if ($timestamp < 0) {
            $timestamp = (int) $timestamp - 2.0 + $ms;
        }

        return static::createFromFormat('U.u', sprintf('%.6F', $timestamp));
    }

    public function add(DateInterval|Duration $interval): static
    {
        if ($interval instanceof Duration) {
            return $this->setUnixTime($this->unixTime()->add($interval));
        } else {
            return parent::add($interval);
        }
    }

    public function sub(DateInterval|Duration $interval): static
    {
        if ($interval instanceof Duration) {
            return $this->setUnixTime($this->unixTime()->sub($interval));
        } else {
            return parent::sub($interval);
        }
    }

    public function durationSince(DateTimeInterface $datePoint): Duration
    {
        $datePoint = $datePoint instanceof DatePoint ? $datePoint : DatePoint::createFromInterface($datePoint);
        return $this->unixTime()->sub($datePoint->unixTime());
    }

    public function modify(string $modifier): static
    {
        return parent::modify($modifier);
    }

    public function setTimestamp(int $timestamp): static
    {
        return parent::setTimestamp($timestamp);
    }

    public function setDate(int $year, int $month, int $day): static
    {
        return parent::setDate($year, $month, $day);
    }

    public function setISODate(int $year, int $week, int $dayOfWeek = 1): static
    {
        return parent::setISODate($year, $week, $dayOfWeek);
    }

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): static
    {
        return parent::setTime($hour, $minute, $second, $microsecond);
    }

    public function setTimezone(DateTimeZone $timezone): static
    {
        return parent::setTimezone($timezone);
    }

    public function getTimezone(): DateTimeZone
    {
        return parent::getTimezone();
    }

    public function setMicrosecond(int $microsecond): static
    {
        //        Not passing phpstan check
        //        if (\PHP_VERSION_ID >= 80400) {
        //            return parent::setMicrosecond($microsecond);
        //        }

        return $this->setTime(...explode('.', $this->format('H.i.s.'.$microsecond)));
    }

    public function getMicrosecond(): int
    {
        //        Not passing phpstan check
        //        if (\PHP_VERSION_ID >= 80400) {
        //            return parent::getMicrosecond();
        //        }

        return (int) $this->format('u');
    }
}
