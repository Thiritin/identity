<?php

namespace Database\Seeders;

use App\Models\Convention;
use Illuminate\Database\Seeder;

class ConventionSeeder extends Seeder
{
    public function run(): void
    {
        $conventions = [
            ['name' => 'Eurofurence 1', 'year' => 1995],
            ['name' => 'Eurofurence 2', 'year' => 1996],
            ['name' => 'Eurofurence 3', 'year' => 1997],
            ['name' => 'Eurofurence 4', 'year' => 1998],
            ['name' => 'Eurofurence 5', 'year' => 1999],
            ['name' => 'Eurofurence 6', 'year' => 2000],
            ['name' => 'Eurofurence 7', 'year' => 2001],
            ['name' => 'Eurofurence 8', 'year' => 2002],
            ['name' => 'Eurofurence 9', 'year' => 2003],
            ['name' => 'Eurofurence X', 'year' => 2004],
            ['name' => 'Eurofurence 11', 'year' => 2005],
            ['name' => 'Eurofurence 12', 'year' => 2006],
            ['name' => 'Eurofurence 13', 'year' => 2007],
            ['name' => 'Eurofurence 14', 'year' => 2008],
            ['name' => 'Eurofurence 15', 'year' => 2009],
            ['name' => 'Eurofurence 16', 'year' => 2010],
            ['name' => 'Eurofurence 17', 'year' => 2011],
            ['name' => 'Eurofurence 18', 'year' => 2012],
            ['name' => 'Eurofurence 19', 'year' => 2013],
            ['name' => 'Eurofurence 20', 'year' => 2014],
            ['name' => 'Eurofurence 21', 'year' => 2015],
            ['name' => 'Eurofurence 22', 'year' => 2016],
            ['name' => 'Eurofurence 23', 'year' => 2017],
            ['name' => 'Eurofurence 24', 'year' => 2018],
            ['name' => 'Eurofurence 25', 'year' => 2019],
            ['name' => 'Eurofurence Online', 'year' => 2020],
            ['name' => 'Eurofurence Online', 'year' => 2021],
            ['name' => 'Eurofurence 26', 'year' => 2022],
            ['name' => 'Eurofurence 27', 'year' => 2023],
            ['name' => 'Eurofurence 28', 'year' => 2024],
            ['name' => 'Eurofurence 29', 'year' => 2025],
        ];

        foreach ($conventions as $convention) {
            Convention::firstOrCreate(
                ['year' => $convention['year']],
                $convention
            );
        }
    }
}
