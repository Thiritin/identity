<?php

namespace Database\Seeders;

use App\Models\Convention;
use Illuminate\Database\Seeder;

class ConventionSeeder extends Seeder
{
    public function run(): void
    {
        $conventions = [
            ['name' => 'Eurofurence 1', 'year' => 1995, 'start_date' => '1995-06-30', 'end_date' => '1995-07-03', 'theme' => null],
            ['name' => 'Eurofurence 2', 'year' => 1996, 'start_date' => '1996-07-18', 'end_date' => '1996-07-22', 'theme' => null],
            ['name' => 'Eurofurence 3', 'year' => 1997, 'start_date' => '1997-08-21', 'end_date' => '1997-08-24', 'theme' => null],
            ['name' => 'Eurofurence 4', 'year' => 1998, 'start_date' => '1998-08-01', 'end_date' => '1998-08-05', 'theme' => null],
            ['name' => 'Eurofurence 5', 'year' => 1999, 'start_date' => '1999-07-22', 'end_date' => '1999-07-25', 'theme' => null],
            ['name' => 'Eurofurence 6', 'year' => 2000, 'start_date' => '2000-08-10', 'end_date' => '2000-08-13', 'theme' => null],
            ['name' => 'Eurofurence 7', 'year' => 2001, 'start_date' => '2001-07-22', 'end_date' => '2001-07-25', 'theme' => null],
            ['name' => 'Eurofurence 8', 'year' => 2002, 'start_date' => '2002-08-15', 'end_date' => '2002-08-18', 'theme' => 'The F.I.A. (Furry Intelligence Agency)'],
            ['name' => 'Eurofurence 9', 'year' => 2003, 'start_date' => '2003-08-21', 'end_date' => '2003-08-24', 'theme' => 'Cunning Little Vixens'],
            ['name' => 'Eurofurence X', 'year' => 2004, 'start_date' => '2004-08-26', 'end_date' => '2004-08-29', 'theme' => 'EFX: The Movie'],
            ['name' => 'Eurofurence 11', 'year' => 2005, 'start_date' => '2005-07-21', 'end_date' => '2005-07-24', 'theme' => 'Songs of the Old Ages'],
            ['name' => 'Eurofurence 12', 'year' => 2006, 'start_date' => '2006-08-23', 'end_date' => '2006-08-27', 'theme' => 'The Hounds of Blackwhite Castle'],
            ['name' => 'Eurofurence 13', 'year' => 2007, 'start_date' => '2007-09-05', 'end_date' => '2007-09-09', 'theme' => 'The Unlucky Thirteen'],
            ['name' => 'Eurofurence 14', 'year' => 2008, 'start_date' => '2008-08-27', 'end_date' => '2008-08-31', 'theme' => 'From Dusk till Dawn'],
            ['name' => 'Eurofurence 15', 'year' => 2009, 'start_date' => '2009-08-26', 'end_date' => '2009-08-30', 'theme' => '1001 Arabian Nights'],
            ['name' => 'Eurofurence 16', 'year' => 2010, 'start_date' => '2010-09-01', 'end_date' => '2010-09-05', 'theme' => 'Serengeti'],
            ['name' => 'Eurofurence 17', 'year' => 2011, 'start_date' => '2011-08-17', 'end_date' => '2011-08-21', 'theme' => 'Kung Fur Hustle'],
            ['name' => 'Eurofurence 18', 'year' => 2012, 'start_date' => '2012-08-29', 'end_date' => '2012-09-02', 'theme' => 'Animalia Romana'],
            ['name' => 'Eurofurence 19', 'year' => 2013, 'start_date' => '2013-08-21', 'end_date' => '2013-08-25', 'theme' => 'Aloha Hawaii!'],
            ['name' => 'Eurofurence 20', 'year' => 2014, 'start_date' => '2014-08-20', 'end_date' => '2014-08-24', 'theme' => 'CSI Berlin'],
            ['name' => 'Eurofurence 21', 'year' => 2015, 'start_date' => '2015-08-19', 'end_date' => '2015-08-23', 'theme' => 'Greenhouse World'],
            ['name' => 'Eurofurence 22', 'year' => 2016, 'start_date' => '2016-08-17', 'end_date' => '2016-08-21', 'theme' => 'Back to the 80s'],
            ['name' => 'Eurofurence 23', 'year' => 2017, 'start_date' => '2017-08-16', 'end_date' => '2017-08-20', 'theme' => 'Ancient Egypt'],
            ['name' => 'Eurofurence 24', 'year' => 2018, 'start_date' => '2018-08-22', 'end_date' => '2018-08-26', 'theme' => 'Aviators - Conquer the Sky'],
            ['name' => 'Eurofurence 25', 'year' => 2019, 'start_date' => '2019-08-14', 'end_date' => '2019-08-18', 'theme' => 'Fractures in Time'],
            ['name' => 'Eurofurence 26', 'year' => 2022, 'start_date' => '2022-08-24', 'end_date' => '2022-08-28', 'theme' => 'Welcome to Tortuga'],
            ['name' => 'Eurofurence 27', 'year' => 2023, 'start_date' => '2023-09-03', 'end_date' => '2023-09-07', 'theme' => 'Black Magic'],
            ['name' => 'Eurofurence 28', 'year' => 2024, 'start_date' => '2024-09-18', 'end_date' => '2024-09-21', 'theme' => 'Cyberpunk'],
            ['name' => 'Eurofurence 29', 'year' => 2025, 'start_date' => '2025-09-03', 'end_date' => '2025-09-06', 'theme' => 'Space Expedition'],
            ['name' => 'Eurofurence 30', 'year' => 2026, 'start_date' => '2026-08-19', 'end_date' => '2026-08-23', 'theme' => 'Fantastic Furry Festival'],
        ];

        foreach ($conventions as $convention) {
            Convention::updateOrCreate(
                ['year' => $convention['year']],
                $convention
            );
        }
    }
}
