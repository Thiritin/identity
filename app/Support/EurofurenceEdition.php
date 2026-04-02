<?php

namespace App\Support;

class EurofurenceEdition
{
    private const EDITIONS = [
        1 => 1995, 2 => 1996, 3 => 1997, 4 => 1998, 5 => 1999,
        6 => 2000, 7 => 2001, 8 => 2002, 9 => 2003, 10 => 2004,
        11 => 2005, 12 => 2006, 13 => 2007, 14 => 2008, 15 => 2009,
        16 => 2010, 17 => 2011, 18 => 2012, 19 => 2013, 20 => 2014,
        21 => 2015, 22 => 2016, 23 => 2017, 24 => 2018, 25 => 2019,
        26 => 2022, 27 => 2023, 28 => 2024, 29 => 2025,
    ];

    private const LAST_MAPPED_EF = 29;

    private const LAST_MAPPED_YEAR = 2025;

    public static function efToYear(int $number): ?int
    {
        if ($number < 1) {
            return null;
        }

        if (isset(self::EDITIONS[$number])) {
            return self::EDITIONS[$number];
        }

        return self::LAST_MAPPED_YEAR + ($number - self::LAST_MAPPED_EF);
    }

    public static function yearToEf(int $year): ?int
    {
        $flipped = array_flip(self::EDITIONS);

        if (isset($flipped[$year])) {
            return $flipped[$year];
        }

        if ($year > self::LAST_MAPPED_YEAR) {
            return self::LAST_MAPPED_EF + ($year - self::LAST_MAPPED_YEAR);
        }

        return null;
    }

    public static function currentEf(): int
    {
        $year = (int) date('Y');
        $ef = self::yearToEf($year);

        return $ef ?? self::LAST_MAPPED_EF;
    }

    /** @return array<int, array{number: int, year: int}> */
    public static function allEditions(): array
    {
        $editions = [];

        foreach (self::EDITIONS as $number => $year) {
            $editions[] = ['number' => $number, 'year' => $year];
        }

        $currentYear = (int) date('Y');
        $nextNumber = self::LAST_MAPPED_EF + 1;
        $nextYear = self::LAST_MAPPED_YEAR + 1;

        while ($nextYear <= $currentYear) {
            $editions[] = ['number' => $nextNumber, 'year' => $nextYear];
            $nextNumber++;
            $nextYear++;
        }

        return $editions;
    }

    /** @return array<int, int> */
    public static function validYears(): array
    {
        return array_column(self::allEditions(), 'year');
    }
}
